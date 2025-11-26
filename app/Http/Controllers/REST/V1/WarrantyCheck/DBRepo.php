<?php

namespace App\Http\Controllers\REST\V1\WarrantyCheck;

use App\Http\Libraries\BaseDBRepo;
use App\Models\Warranty;
use Exception;

class DBRepo extends BaseDBRepo
{
    /**
     * Konstruktor standar.
     */
    public function __construct(?array $payload = [], ?array $file = [], ?array $auth = [])
    {
        // DBRepo menggunakan parent::__construct sesuai polanya
        parent::__construct($payload, $file, $auth);
    }
    
    /*
    |--------------------------------------------------------------------------
    | DATABASE TRANSACTION
    |--------------------------------------------------------------------------
    */

    /**
     * Mencari data garansi berdasarkan nomor kartu garansi ATAU nomor seri produk.
     *
     * @return object
     */
    public function getWarrantyData()
    {
        try {
            $code = $this->payload['code'];

            // Mulai query dari model Warranty
            $warranty = Warranty::query()
                // Eager load semua relasi yang diperlukan untuk menampilkan data lengkap
                // Warranty -> Sale -> Variant -> Product
                ->with(['sale.variant.product'])
                // Kondisi 1: Cari di kolom 'card_number' di tabel 'warranties'
                ->where('card_number', $code)
                // Kondisi 2 (ATAU): Cari di kolom 'serial_number' di tabel relasi 'sale'
                ->orWhereHas('sale', function ($query) use ($code) {
                    $query->where('serial_number', $code);
                })
                // Ambil hasil pertama yang cocok
                ->first();

            // Jika data ditemukan
            if ($warranty) {
                return (object) [
                    'status' => true,
                    'data' => $warranty->toArray(),
                    'message' => 'Warranty found.'
                ];
            }

            // Jika data tidak ditemukan setelah pencarian selesai
            return (object) [
                'status' => true,
                'data' => null,
                'message' => 'No matching warranty or serial number found.'
            ];
        } catch (Exception $e) {
            return (object) [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
