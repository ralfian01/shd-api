<?php

namespace App\Http\Controllers\REST\V1\Pos\Carts;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;

class Insert extends BaseREST
{
    public function __construct(
        ?array $payload = [],
        ?array $file = [],
        ?array $auth = [] // Kita asumsikan outlet_id & employee_id ada di sini
    ) {
        $this->payload = $payload;
        $this->file = $file;
        $this->auth = $auth;
        return $this;
    }

    /**
     * @var array
     * --- CONTOH PAYLOAD ---
     * 
     * Opsi 1: Keranjang Kosong
     * {}
     *
     * Opsi 2: Keranjang dengan Customer yang sudah dipilih
     * { "customer_id": 5 }
     */
    protected $payloadRules = [
        // customer_id bersifat opsional, tapi jika dikirim, harus ada di tabel customers
        'customer_id' => 'nullable|integer|exists:customers,id',
    ];

    protected $privilegeRules = [];

    protected function mainActivity()
    {
        return $this->nextValidation();
    }

    private function nextValidation()
    {
        // Validasi tambahan untuk memastikan outlet_id dan employee_id ada di data otentikasi
        // if (empty($this->payload['outlet_id']) || empty($this->payload['employee_id'])) {
        if (empty($this->auth['outlet_id']) || empty($this->auth['employee_id'])) {
            return $this->error(
                (new Errors)
                    ->setMessage(401, 'Authentication context is missing required outlet or employee information.')
            );
        }

        return $this->insert();
    }

    /** 
     * Function to insert data 
     * @return object
     */
    public function insert()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $result = $dbRepo->createCart();

        if ($result->status) {
            return $this->respond(201, $result->data);
        }

        return $this->error(500, ['reason' => $result->message]);
    }
}
