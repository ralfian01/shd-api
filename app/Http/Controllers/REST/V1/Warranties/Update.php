<?php

namespace App\Http\Controllers\REST\V1\Warranties;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;

class Update extends BaseREST
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
     * Aturan validasi HANYA untuk field yang diizinkan untuk diubah.
     * @var array
     */
    protected $payloadRules = [
        'service_tag' => 'sometimes|required|string|max:100',
    ];

    protected $privilegeRules = [
        // Contoh: 'UPDATE_WARRANTY_TAG'
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
     * Menangani validasi logika bisnis.
     */
    private function nextValidation()
    {
        // Validasi utama: pastikan data garansi yang akan di-edit memang ada.
        if (!DBRepo::checkWarrantyExists($this->payload['id'])) {
            return $this->error(404, ['reason' => 'Warranty record not found']);
        }

        return $this->update();
    }

    /**
     * Function executor untuk memperbarui data.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $result = $dbRepo->updateData();

        if ($result->status) {
            return $this->respond($result->data);
        }

        return $this->error(500, ['reason' => $result->message]);
    }
}
