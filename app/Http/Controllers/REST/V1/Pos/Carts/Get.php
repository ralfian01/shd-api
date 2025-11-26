<?php

namespace App\Http\Controllers\REST\V1\Pos\Carts;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;

class Get extends BaseREST
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

    protected $payloadRules = [
        // 'id' dari URI
        'id' => 'required|integer|exists:carts,id',
    ];

    protected $privilegeRules = [];
    protected function mainActivity()
    {
        return $this->nextValidation();
    }
    private function nextValidation()
    {
        return $this->get();
    }

    public function get()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $result = $dbRepo->getCart($this->payload['id']); // Memanggil method yang sudah ada

        if ($result->status) {
            return $this->respond(200, $result->data);
        }

        return $this->error((new Errors)->setMessage(404, 'Cart not found.'));
    }
}
