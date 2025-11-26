<?php

namespace App\Http\Controllers\REST\V1\Manage\Summary;

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
     * Endpoint ini tidak menerima parameter filter, jadi payloadRules kosong.
     * @var array
     */
    protected $payloadRules = [];

    /**
     * Endpoint ini adalah bagian dari manajemen, jadi memerlukan hak akses.
     * @var array
     */
    protected $privilegeRules = [
        // Contoh: 'VIEW_SUMMARY_DASHBOARD'
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
     * Tidak ada validasi bisnis, langsung panggil function executor.
     */
    private function nextValidation()
    {
        return $this->get();
    }

    /**
     * Function executor untuk mengambil data summary.
     * @return \Illuminate\Http\JsonResponse
     */
    public function get()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $result = $dbRepo->getSummaryData();

        if ($result->status) {
            return $this->respond($result->data);
        }

        return $this->error(500, ['reason' => $result->message]);
    }
}
