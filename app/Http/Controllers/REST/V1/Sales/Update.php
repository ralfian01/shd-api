<?php

namespace App\Http\Controllers\REST\V1\Sales;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;
use Illuminate\Validation\Rule;

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
     * Properti ini dikosongkan karena aturan validasi serial_number bersifat dinamis.
     * @var array
     */
    protected $payloadRules = [];

    protected $privilegeRules = [
        // Contoh: 'EDIT_SALE_TRANSACTION'
    ];

    /**
     * Metode utama untuk membangun aturan validasi bawaan Laravel.
     * @return \Illuminate\Http\JsonResponse
     */
    protected function mainActivity()
    {
        $saleId = $this->payload['id'] ?? null;

        // Mendefinisikan aturan validasi. Perhatikan kita hanya memasukkan field
        // yang boleh diubah. Field seperti quantity, price, dll. diabaikan.
        $this->payloadRules = [
            'buyer_name' => 'sometimes|required|string|max:255',
            'buyer_address' => 'sometimes|required|string',
            'buyer_phone' => 'sometimes|required|string|max:20',
            'serial_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                // Pastikan serial_number unik, dengan mengabaikan record penjualan ini sendiri
                Rule::unique('sales')->ignore($saleId),
            ],
        ];

        return $this->nextValidation();
    }

    /**
     * Menangani validasi logika bisnis.
     */
    private function nextValidation()
    {
        // Validasi utama: pastikan data penjualan yang akan di-edit memang ada.
        if (!DBRepo::checkSaleExists($this->payload['id'])) {
            return $this->error(404, ['reason' => 'Sale record not found']);
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
            return $this->respond(200, $result->data);
        }

        return $this->error(500, ['reason' => $result->message]);
    }
}
