<?php

namespace App\Http\Controllers\REST\V1\WarrantyCheck;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;

class Get extends BaseREST
{
    /**
     * Konstruktor yang benar sesuai dengan struktur kode.
     */
    public function __construct(?array $payload = [], ?array $file = [], ?array $auth = [])
    {
        $this->payload = $payload;
        $this->file = $file;
        $this->auth = $auth;
        return $this;
    }

    /**
     * Aturan validasi untuk parameter 'code' yang bisa berupa
     * nomor seri atau nomor kartu garansi.
     * @var array
     */
    protected $payloadRules = [
        'code' => 'required|string|max:100',
    ];

    /**
     * Endpoint ini kemungkinan besar bersifat publik, jadi tidak perlu hak akses.
     * @var array
     */
    protected $privilegeRules = [];

    /**
     * Metode utama yang memulai aktivitas.
     * @return \Illuminate\Http\JsonResponse
     */
    protected function mainActivity()
    {
        return $this->nextValidation();
    }

    /**
     * Tidak ada validasi bisnis tambahan yang diperlukan,
     * jadi kita langsung memanggil function executor.
     */
    private function nextValidation()
    {
        return $this->get();
    }

    /**
     * Function executor untuk mengambil data garansi.
     * @return \Illuminate\Http\JsonResponse
     */
    public function get()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $result = $dbRepo->getWarrantyData();

        // Kasus 1: Sukses dan data ditemukan
        if ($result->status && !is_null($result->data)) {
            return $this->respond(200, $result->data);
        }

        // Kasus 2: Sukses tapi data tidak ditemukan (validasi gagal)
        if ($result->status && is_null($result->data)) {
            return $this->error(404, ['reason' => $result->message]);
        }

        // Kasus 3: Terjadi error di server
        return $this->error(500, ['reason' => $result->message]);
    }
}
