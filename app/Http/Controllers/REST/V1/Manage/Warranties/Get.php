<?php

namespace App\Http\Controllers\REST\V1\Manage\Warranties;

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
     * Aturan validasi bawaan Laravel untuk parameter filter.
     * @var array
     */
    protected $payloadRules = [
        'keyword' => 'sometimes|string|max:100',
        'service_tag' => 'sometimes|string|max:100',
        'date_start' => 'sometimes|date_format:Y-m-d',
        'date_end' => 'sometimes|date_format:Y-m-d|after_or_equal:date_start',
    ];

    protected $privilegeRules = [
        // Contoh: 'VIEW_WARRANTIES'
    ];

    /**
     * Metode utama yang memulai aktivitas.
     * @return \Illuminate\Http\JsonResponse
     */
    protected function mainActivity()
    {
        return $this->nextValidation();
    }

    /**
     * Langsung memanggil function executor karena tidak ada validasi bisnis tambahan.
     */
    private function nextValidation()
    {
        return $this->get();
    }

    /**
     * Function executor untuk mengambil data.
     * @return \Illuminate\Http\JsonResponse
     */
    public function get()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $result = $dbRepo->getData();

        if ($result->status) {
            return $this->respond($result->data);
        }

        return $this->error(500, ['reason' => $result->message]);
    }
}
