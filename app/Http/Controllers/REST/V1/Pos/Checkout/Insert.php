<?php

namespace App\Http\Controllers\REST\V1\Pos\Checkout;

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
     * {
     *     "cart_id": 123,
     *     "payment": {
     *         "payment_method_id": 1,
     *         "cash_received": 200000
     *     }
     * }
     */
    protected $payloadRules = [
        'cart_id' => 'required|integer|exists:carts,id',
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
        $validation = DBRepo::validateCheckout($this->payload);
        if (!$validation->status) {
            return $this->error((new Errors)->setMessage(409, $validation->message));
        }
        return $this->insert();
    }

    public function insert()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $result = $dbRepo->executeCheckout();

        if ($result->status) {
            return $this->respond(201, $result->data);
        }
        return $this->error(500, ['reason' => $result->message]);
    }
}
