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
                // 1. Ambil data produk terkait untuk mendapatkan durasi garansi
                $variant = Variant::with('product')->find($this->payload['variant_id']);
                $durationMonths = $variant->product->warranty_duration_months;

                // 2. Parse tanggal pembelian menggunakan Carbon
                $purchaseDate = Carbon::parse($this->payload['purchase_date']);

                // 3. Hitung tanggal kedaluwarsa garansi
                $expiresAt = $purchaseDate->copy()->addMonths($durationMonths);

                // 4. Buat record penjualan
                $sale = Sale::create(Arr::except($this->payload, ['serial_numbers']));

                // 5. Lakukan loop sebanyak kuantitas untuk membuat garansi
                for ($i = 0; $i < $this->payload['quantity']; $i++) {
                    // ... (logika generate nomor kartu dan serial number tetap sama)
                    $warrantyCardNumber = 'GAR-' . date('Ymd') . '-' . Str::upper(Str::random(6)) . '-' . ($i + 1);
                    $serialNumber = $this->payload['serial_numbers'][$i] ?? null;

                    // 6. Buat record garansi dengan menyertakan 'expires_at'
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
                    'data' => $sale->id
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
