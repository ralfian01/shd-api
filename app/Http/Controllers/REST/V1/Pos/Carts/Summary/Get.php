<?php

namespace App\Http\Controllers\REST\V1\Pos\Carts\Summary;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;
use App\Http\Controllers\REST\V1\Pos\Carts\DBRepo; // Menggunakan DBRepo Carts utama

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
        // Endpoint ini tidak memerlukan parameter dari klien,
        // karena outlet_id diambil dari data otentikasi.
        'page' => 'nullable|integer|min:1',
        'per_page' => 'nullable|integer|min:1|max:100',
    ];

    protected $privilegeRules = [];
    protected function mainActivity()
    {
        return $this->nextValidation();
    }

    private function nextValidation()
    {
        if (empty($this->auth['outlet_id'])) {
            return $this->error((new Errors)->setMessage(401, 'Authentication context is missing outlet information.'));
        }
        return $this->get();
    }

    public function get()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $result = $dbRepo->getActiveCartsSummary();

        if ($result->status) {
            return $this->respond(200, $result->data);
        }

        return $this->respond(200, null);
    }
}
