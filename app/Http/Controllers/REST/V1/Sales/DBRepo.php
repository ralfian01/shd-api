<?php

namespace App\Http\Controllers\REST\V1\Sales;

use App\Http\Libraries\BaseDBRepo;
use App\Models\Sale;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; // Diperlukan untuk generate string acak

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
                ->with(['warranty', 'variant.product']);

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
            // DB::transaction memastikan bahwa jika salah satu query gagal (misal, pembuatan garansi),
            // maka query pembuatan penjualan juga akan dibatalkan (rollback).
            $saleData = DB::transaction(function () {
                // 1. Buat data penjualan dari payload
                $sale = Sale::create(Arr::only($this->payload, [
                    'variant_id',
                    'invoice_code',
                    'quantity',
                    'unit_price',
                    'purchase_date',
                    'buyer_name',
                    'buyer_address',
                    'buyer_phone',
                    'serial_number'
                ]));

                // 2. Generate data untuk garansi secara otomatis
                $warrantyCardNumber = 'GAR-' . date('Ymd') . '-' . Str::upper(Str::random(6));
                $expressServiceCode = 'EXP-' . Str::upper(Str::random(8));

                // 3. Buat data garansi yang terhubung langsung dengan penjualan yang baru dibuat
                $sale->warranty()->create([
                    'card_number' => $warrantyCardNumber,
                    'express_service_code' => $expressServiceCode,
                    'service_tag' => 'STANDARD' // Contoh nilai default
                ]);

                // 4. Muat ulang relasi garansi untuk disertakan dalam response
                return $sale->load('warranty');
            });

            return (object) [
                'status' => true,
                'data' => $saleData->toArray(),
            ];
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
