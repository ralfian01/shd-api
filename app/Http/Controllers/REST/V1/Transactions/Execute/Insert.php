<?php

namespace App\Http\Controllers\REST\V1\Transactions\Execute;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;

class Insert extends BaseREST
{
    public function __construct(
        ?array $payload = [],
        ?array $file = [],
        ?array $auth = [],
    ) {
        $this->payload = $payload;
        $this->file = $file;
        $this->auth = $auth;
        return $this;
    }

    protected $payloadRules = [
        'quote' => 'required|array',
        'quote.grand_total' => 'required|numeric',
        'quote.items' => 'required|array|min:1',

        'customer' => 'required|array',
        'customer.name' => 'required|string|max:100',
        'customer.phone_number' => 'required|string|max:255',
        'customer.email' => 'nullable|email',
        'customer.address' => 'nullable|string',

        'member_id' => 'nullable|integer|exists:members,id',

        'payment' => 'required|array',
        'payment.payment_method_id' => 'required|integer|exists:payment_methods,id',
        'payment.cash_received' => 'nullable|numeric',
    ];

    protected $privilegeRules = [];

    protected function mainActivity()
    {
        return $this->nextValidation();
    }

    private function nextValidation()
    {
        // Validasi simpel pembayaran
        if (isset($this->payload['payment']['cash_received'])) {
            if ($this->payload['payment']['cash_received'] < $this->payload['quote']['grand_total']) {
                return $this->error((new Errors)->setMessage(400, 'Insufficient cash received.'));
            }
        }
        return $this->execute();
    }

    public function execute()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $result = $dbRepo->executeTransaction();

        if ($result->status) {
            return $this->respond(201, $result->data);
        }

        return $this->error(500, ['reason' => $result->message]);
    }
}
