<?php

namespace App\Http\Controllers\REST\V1\Sales;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;

class Insert extends BaseREST
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
     * Aturan validasi bawaan Laravel untuk data penjualan.
     * @var array
     */
    protected $payloadRules = [
        'variant_id' => 'required|integer|exists:variants,id',
        'invoice_code' => 'required|string|max:100|unique:sales,invoice_code',
        'quantity' => 'required|integer|min:1',
        'unit_price' => 'required|numeric|min:0',
        'purchase_date' => 'required|date_format:Y-m-d H:i:s',
        'buyer_name' => 'required|string|max:255',
        'buyer_address' => 'required|string',
        'buyer_phone' => 'required|string|max:20',
        'serial_number' => 'nullable|string|max:255|unique:sales,serial_number',
    ];

    /**
     * Aturan hak akses untuk endpoint ini.
     * @var array
     */
    protected $privilegeRules = [
        // Contoh: 'CREATE_SALE_TRANSACTION'
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
     * Dalam kasus ini, semua validasi dasar (seperti keberadaan varian)
     * sudah ditangani oleh $payloadRules, jadi kita bisa langsung lanjut.
     * Jika ada aturan seperti "cek stok varian", ini adalah tempatnya.
     */
    private function nextValidation()
    {
        // Contoh validasi bisnis tambahan (saat ini dikomentari):
        // if (!DBRepo::isVariantInStock($this->payload['variant_id'], $this->payload['quantity'])) {
        //     return $this->error(409, ['reason' => 'Insufficient stock for the selected variant.']);
        // }

        return $this->insert();
    }

    /**
     * Function executor untuk menyisipkan data.
     * @return \Illuminate\Http\JsonResponse
     */
    public function insert()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $result = $dbRepo->insertData();

        if ($result->status) {
            return $this->respond(201, $result->data);
        }

        return $this->error(500, ['reason' => $result->message]);
    }
}
