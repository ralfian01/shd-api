<?php

namespace App\Http\Controllers\REST\V1\Manage\Warranties;

use App\Http\Libraries\BaseDBRepo;
use App\Models\Warranty;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DBRepo extends BaseDBRepo
{
    public function __construct(?array $payload = [], ?array $file = [], ?array $auth = [])
    {
        parent::__construct($payload, $file, $auth);
    }

    /*
    |--------------------------------------------------------------------------
    | TOOLS - Static validation methods
    |--------------------------------------------------------------------------
    */
    /**
     * Memeriksa apakah data garansi dengan ID tertentu ada.
     * @param int $id
     * @return bool
     */
    public static function checkWarrantyExists(int $id): bool
    {
        return Warranty::where('id', $id)->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | DATABASE TRANSACTION
    |--------------------------------------------------------------------------
    */
    /**
     * Mengupdate data garansi (kasus khusus).
     * Hanya field `service_tag` yang diizinkan untuk diubah.
     *
     * @return object
     */
    public function updateData()
    {
        try {
            $warrantyData = DB::transaction(function () {
                $warranty = Warranty::find($this->payload['id']);

                // --- BAGIAN PALING PENTING (SPECIAL CASE) ---
                // Secara eksplisit HANYA mengambil 'service_tag' dari payload.
                // Ini adalah gerbang keamanan yang mencegah field lain (seperti card_number)
                // agar tidak ter-update, bahkan jika client mengirimkannya.
                $allowedFields = Arr::only($this->payload, ['service_tag', 'voided_at']);

                // Lakukan update hanya jika ada data yang diizinkan untuk diubah.
                if (!empty($allowedFields)) {
                    $warranty->update($allowedFields);
                }

                // Muat ulang relasi lengkap untuk disertakan dalam response
                return $warranty->load(['sale.variant.product']);
            });

            return (object) [
                'status' => true,
                'data' => $warrantyData->toArray(),
            ];
        } catch (Exception $e) {
            return (object) [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * --- METHOD BARU ---
     * Mengambil data garansi dari database dengan filter dan paginasi.
     *
     * @return object
     */
    public function getData()
    {
        try {
            $query = Warranty::query()
                // Eager load relasi untuk menampilkan data yang kaya dan lengkap
                ->with(['sale.variant.product']);

            // Kasus 1: Mengambil satu data garansi detail berdasarkan ID
            if (isset($this->payload['id'])) {
                $warranty = $query->find($this->payload['id']);
                return (object) [
                    'status' => !is_null($warranty),
                    'data' => $warranty ? $warranty->toArray() : null,
                    'message' => $warranty ? 'Data found' : 'Warranty record not found'
                ];
            }

            // Kasus 2: Mengambil daftar garansi dengan filter

            // Filter berdasarkan rentang tanggal pembelian (melalui relasi 'sale')
            if (isset($this->payload['date_start']) && isset($this->payload['date_end'])) {
                $query->whereHas('sale', function ($q) {
                    $q->whereBetween('purchase_date', [
                        $this->payload['date_start'] . ' 00:00:00',
                        $this->payload['date_end'] . ' 23:59:59'
                    ]);
                });
            }

            // Filter berdasarkan service_tag
            if (isset($this->payload['service_tag'])) {
                $query->where('service_tag', $this->payload['service_tag']);
            }

            // Filter berdasarkan kata kunci
            if (isset($this->payload['keyword'])) {
                $keyword = $this->payload['keyword'];
                $query->where(function ($q) use ($keyword) {
                    // Cari di kolom tabel 'warranties'
                    $q->where('card_number', 'LIKE', "%{$keyword}%")
                        // Cari di kolom tabel relasi 'sale'
                        ->orWhereHas('sale', function ($subQuery) use ($keyword) {
                            $subQuery->where('invoice_code', 'LIKE', "%{$keyword}%")
                                ->orWhere('buyer_name', 'LIKE', "%{$keyword}%")
                                ->orWhere('serial_number', 'LIKE', "%{$keyword}%");
                        });
                });
            }

            // Ambil data dengan paginasi dan urutkan berdasarkan yang terbaru
            $warranties = $query->latest()->paginate(15);

            return (object) [
                'status' => true,
                'data' => $warranties->toArray(),
            ];
        } catch (Exception $e) {
            return (object) [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
