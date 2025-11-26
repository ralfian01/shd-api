<?php

namespace App\Http\Controllers\REST\V1\Sales;

use App\Http\Libraries\BaseDBRepo;
use App\Models\Sale;
use App\Models\Variant;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DBRepo extends BaseDBRepo
{
    /**
     * Konstruktor standar.
     */
    public function __construct(?array $payload = [], ?array $file = [], ?array $auth = [])
    {
        parent::__construct($payload, $file, $auth);
    }

    /**
     * --- METHOD STATIS BARU ---
     * Memeriksa apakah data penjualan dengan ID tertentu ada.
     * @param int $id
     * @return bool
     */
    public static function checkSaleExists(int $id): bool
    {
        return Sale::where('id', $id)->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | DATABASE TRANSACTION
    |--------------------------------------------------------------------------
    */

    /**
     * --- METHOD BARU ---
     * Mengambil data penjualan dari database dengan filter dan paginasi.
     *
     * @return object
     */
    public function getData()
    {
        try {
            $query = Sale::query()
                // Eager load relasi untuk menghindari N+1 problem dan memperkaya data.
                // Kita ambil data garansi, varian, dan juga produk dari varian tersebut.
                ->with(['warranties', 'variant.product']);

            // Kasus 1: Mengambil satu data penjualan detail berdasarkan ID
            if (isset($this->payload['id'])) {
                $sale = $query->find($this->payload['id']);
                return (object) [
                    'status' => !is_null($sale),
                    'data' => $sale ? $sale->toArray() : null,
                    'message' => $sale ? 'Data found' : 'Sale record not found'
                ];
            }

            // Kasus 2: Mengambil daftar penjualan dengan filter

            // Filter berdasarkan rentang tanggal pembelian
            if (isset($this->payload['date_start']) && isset($this->payload['date_end'])) {
                $query->whereBetween('purchase_date', [
                    $this->payload['date_start'] . ' 00:00:00',
                    $this->payload['date_end'] . ' 23:59:59'
                ]);
            }

            // Filter berdasarkan kata kunci
            if (isset($this->payload['keyword'])) {
                $keyword = $this->payload['keyword'];
                $query->where(function ($q) use ($keyword) {
                    // Cari di kolom tabel 'sales'
                    $q->where('invoice_code', 'LIKE', "%{$keyword}%")
                        ->orWhere('buyer_name', 'LIKE', "%{$keyword}%")
                        ->orWhere('serial_number', 'LIKE', "%{$keyword}%")
                        // Cari di relasi 'variant' -> 'product'
                        ->orWhereHas('variant.product', function ($subQuery) use ($keyword) {
                            $subQuery->where('name', 'LIKE', "%{$keyword}%");
                        });
                });
            }

            // Ambil data dengan paginasi dan urutkan berdasarkan yang terbaru
            $sales = $query->latest('purchase_date')->paginate(15);

            return (object) [
                'status' => true,
                'data' => $sales->toArray(),
            ];
        } catch (Exception $e) {
            return (object) [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Menyimpan data penjualan dan secara otomatis membuat data garansi
     * dalam satu transaksi database yang aman.
     *
     * @return object
     */
    public function insertData()
    {
        try {
            return DB::transaction(function () {
                // ... (Logika mengambil variant, durasi, dan menghitung expires_at tetap sama)
                $variant = \App\Models\Variant::with('product')->find($this->payload['variant_id']);
                $durationMonths = $variant->product->warranty_duration_months;
                $purchaseDate = \Carbon\Carbon::parse($this->payload['purchase_date']);
                $expiresAt = $purchaseDate->copy()->addMonths($durationMonths);

                $sale = \App\Models\Sale::create(Arr::except($this->payload, ['serial_numbers']));

                // --- PERUBAHAN LOGIKA UTAMA: PENGECEKAN SERIAL NUMBER ---
                $manualSerialNumbersProvided = isset($this->payload['serial_numbers']) && !empty($this->payload['serial_numbers']);

                for ($i = 0; $i < $this->payload['quantity']; $i++) {
                    $serialNumber = null;

                    if ($manualSerialNumbersProvided) {
                        // Skenario 1: Ambil serial number dari payload manual
                        $serialNumber = $this->payload['serial_numbers'][$i];
                    } else {
                        // Skenario 2: Generate serial number secara otomatis
                        // Format: SN-[10 KARAKTER ACAK]-[TIMESTAMP]
                        $serialNumber = 'SN-' . strtoupper(Str::random(10)) . '-' . time() . $i;
                    }

                    $warrantyCardNumber = 'GAR-' . date('Ymd') . '-' . Str::upper(Str::random(6)) . '-' . ($i + 1);

                    // Buat record garansi dengan serial number yang sudah ditentukan
                    $sale->warranties()->create([
                        'serial_number' => $serialNumber,
                        'card_number' => $warrantyCardNumber,
                        'express_service_code' => 'EXP-' . Str::upper(Str::random(8)),
                        'service_tag' => 'STANDARD',
                        'expires_at' => $expiresAt
                    ]);
                }

                return (object) [
                    'status' => true,
                    'data' => ['id' => $sale->id]
                ];
            });
        } catch (Exception $e) {
            return (object) [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }


    /**
     * --- METHOD BARU ---
     * Mengupdate data penjualan (kasus khusus).
     * Hanya field tertentu yang diizinkan untuk diubah.
     *
     * @return object
     */
    public function updateData()
    {
        try {
            $saleData = DB::transaction(function () {
                $sale = Sale::find($this->payload['id']);

                // --- BAGIAN PALING PENTING (SPECIAL CASE) ---
                // Kita secara eksplisit HANYA mengambil field yang diizinkan dari payload.
                // Ini mencegah field sensitif seperti `quantity` atau `unit_price`
                // agar tidak ter-update meskipun dikirim oleh client.
                $allowedFields = Arr::only($this->payload, [
                    'buyer_name',
                    'buyer_address',
                    'buyer_phone',
                    'serial_number'
                ]);

                // Lakukan update hanya jika ada data yang diizinkan untuk diubah.
                if (!empty($allowedFields)) {
                    $sale->update($allowedFields);
                }

                // Muat ulang relasi garansi agar response tetap konsisten
                return $sale->load('warranty');
            });

            return (object) [
                'status' => true,
                'data' => $saleData->toArray(),
            ];
        } catch (Exception $e) {
            return (object) [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
