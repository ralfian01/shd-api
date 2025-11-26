<?php

namespace App\Http\Controllers\REST\V1\Pos\Carts\Items;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;

class Insert extends BaseREST
{
    public function __construct(
        ?array $payload = [],
        ?array $file = [],
        ?array $auth = []
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
     * 1. Produk Konsumsi:
     * { "type": "CONSUMPTION", "variant_id": 1, "quantity": 2 }
     *
     * 2. Sewa Waktu Tetap:
     * { "type": "RENTAL_FIXED", "resource_id": 1, "quantity": 3, "start_datetime": "2025-12-25 10:00:00" }
     *
     * 3. Sewa Waktu Dinamis (Mulai Sesi):
     * { "type": "RENTAL_DYNAMIC", "resource_id": 2, "start_datetime": "2025-09-11 10:30:00" }
     *
     * 4. Paket:
     * { "type": "PACKAGE", "package_id": 1, "quantity": 1 }
     */
    protected $payloadRules = [
        // ID Keranjang dari URI
        'id' => 'required|integer|exists:carts,id',

        // Aturan umum
        'type' => 'required|string|in:CONSUMPTION,RENTAL_FIXED,RENTAL_DYNAMIC,PACKAGE',
        'quantity' => 'required_if:type,CONSUMPTION,RENTAL_FIXED,PACKAGE|numeric|min:1',

        // Aturan untuk 'CONSUMPTION'
        'variant_id' => 'required_if:type,CONSUMPTION|integer|exists:product_variants,variant_id',

        // Aturan untuk 'RENTAL_FIXED' dan 'RENTAL_DYNAMIC'
        'resource_id' => 'required_if:type,RENTAL_FIXED,RENTAL_DYNAMIC|integer|exists:resources,resource_id',
        'start_datetime' => 'required_if:type,RENTAL_FIXED,RENTAL_DYNAMIC|date_format:Y-m-d H:i:s',

        // Aturan untuk 'PACKAGE'
        'package_id' => 'required_if:type,PACKAGE|integer|exists:packages,id',
    ];

    protected $privilegeRules = [];

    protected function mainActivity()
    {
        return $this->nextValidation();
    }

    private function nextValidation()
    {
        // Semua validasi bisnis yang kompleks (stok, jadwal, konflik)
        // ditangani di dalam DBRepo, sehingga controller ini tetap bersih.
        return $this->insert();
    }

    public function insert()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $result = $dbRepo->addItem($this->payload['id']);

        if ($result->status) {
            // Mengembalikan seluruh data keranjang yang sudah diperbarui
            return $this->respond(201, $result->data);
        }

        // Jika kalkulasi gagal (misal: stok tidak cukup), kembalikan error 409 Conflict
        return $this->error(409, ['reason' => $result->message]);
    }
}
