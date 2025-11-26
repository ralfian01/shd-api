<?php

namespace App\Http\Controllers\REST\V1\Transactions\Booking;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;

class Insert extends BaseREST
{
    public function __construct(?array $payload = [], ?array $file = [], ?array $auth = [])
    {
        $this->payload = $payload;
        $this->file = $file;
        $this->auth = $auth;
        return $this;
    }

    /**
     * @var array
     * --- CONTOH PAYLOAD BARU ---
     * {
     *     "customer": { "name": "Budi Santoso", "phone_number": "08123456789" },
     *     "member_id": 123, // Opsional, jika pelanggan adalah member
     *     "booking_item": {
     *         "resource_id": 1, "unit_id": 2, "quantity": 2, "start_datetime": "2025-12-10 19:00:00"
     *     },
     *     "payment": {
     *         "payment_method_id": 1, "cash_received": 200000
     *     }
     * }
     */
    protected $payloadRules = [
        'customer' => 'required|array', // Data customer sekarang wajib
        'customer.name' => 'required|string|max:100',
        'customer.phone_number' => 'required|string|max:255',
        'customer.email' => 'nullable|email',
        'customer.address' => 'nullable|string',

        'member_id' => 'nullable|integer|exists:members,id', // Validasi member_id opsional

        'booking_item' => 'required|array',
        'booking_item.resource_id' => 'required|integer|exists:resources,resource_id',
        'booking_item.unit_id' => 'required|integer|exists:units,unit_id',
        'booking_item.quantity' => 'required|numeric|min:0.1',
        'booking_item.start_datetime' => 'required|date_format:Y-m-d H:i:s|after_or_equal:now',

        'payment' => 'required|array',
        'payment.payment_method_id' => 'required|integer|exists:payment_methods,id',
        'payment.cash_received' => 'nullable|numeric|min:0',
    ];

    protected $privilegeRules = [];

    protected function mainActivity()
    {
        return $this->nextValidation();
    }

    private function nextValidation()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);

        // Validasi 1: Cek ketersediaan resource SEKARANG
        if (!$dbRepo->checkAvailabilityForTransaction()) {
            return $this->error((new Errors)->setMessage(409, 'Resource is no longer available at the requested time.'));
        }

        // Validasi 2: Cek logika pembayaran
        $paymentValidation = $dbRepo->validatePayment();
        if (!$paymentValidation->status) {
            return $this->error((new Errors)->setMessage(400, $paymentValidation->message));
        }

        return $this->insert();
    }

    public function insert()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $insert = $dbRepo->insertData();

        if ($insert->status) {
            return $this->respond(201, $insert->data);
        }

        return $this->error(500, ['reason' => $insert->message]);
    }
}
