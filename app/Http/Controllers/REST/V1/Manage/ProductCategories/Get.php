<?php

namespace App\Http\Controllers\REST\V1\Manage\ProductCategories;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors; // Di-include untuk konsistensi

class Get extends BaseREST
{
    /**
     * Konstruktor standar sesuai template.
     */
    public function __construct(array $payload = [], ?array $file = [], ?array $auth = [])
    {
        parent::__construct($payload, $file, $auth);
    }

    /**
     * Aturan validasi untuk query parameter (misalnya untuk filter & pencarian).
     * 'sometimes' berarti aturan hanya berlaku jika field-nya ada di request.
     * @var array
     */
    protected $payloadRules = [
        'keyword' => 'sometimes|string|max:100'
    ];

    /**
     * Properti untuk aturan hak akses (privilege). Bisa dikosongkan jika tidak ada.
     * @var array
     */
    protected $privilegeRules = [
        // Contoh: 'VIEW_PRODUCT_CATEGORY'
    ];

    /**
     * Metode utama yang memulai aktivitas. Selalu memanggil nextValidation().
     * @return \Illuminate\Http\JsonResponse
     */
    protected function mainActivity()
    {
        return $this->nextValidation();
    }

    /**
     * Menangani langkah validasi selanjutnya.
     * Untuk GET, biasanya tidak ada validasi bisnis tambahan sebelum fetch data,
     * jadi langsung memanggil function executor.
     */
    private function nextValidation()
    {
        return $this->get();
    }

    /**
     * Function executor yang sebenarnya untuk mengambil data.
     * @return \Illuminate\Http\JsonResponse
     */
    public function get()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $result = $dbRepo->getData();

        if ($result->status) {
            return $this->respond($result->data);
        }

        // Jika DBRepo mengembalikan status false (misal: ID tidak ditemukan atau error DB)
        // Kembalikan response error yang sesuai.
        // Untuk "Not Found", DBRepo akan mengembalikan data null, jadi kita tidak perlu
        // menganggapnya sebagai server error 500.
        return $this->error(500, ['reason' => $result->message]);
    }
}
