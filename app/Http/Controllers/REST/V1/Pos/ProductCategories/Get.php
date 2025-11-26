<?php

namespace App\Http\Controllers\REST\V1\Pos\ProductCategories;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;
use App\Http\Controllers\REST\V1\Manage\ProductCategories\DBRepo;

class Get extends BaseREST
{
    public function __construct(
        ?array $p = [],
        ?array $f = [],
        ?array $a = []
    ) {
        $this->payload = $p;
        $this->file = $f;
        $this->auth = $a;
        return $this;
    }
    protected $payloadRules = [
        'id' => 'nullable|integer|exists:employees,id',
        'keyword' => 'nullable|string|min:2',
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
        return $this->get();
    }
    public function get()
    {
        $this->payload = array_merge($this->payload, [
            'business_id' => $this->auth['business_id']
        ]);
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);

        $r = $dbRepo->getData();

        if ($r->status) {
            return $this->respond(200, $r->data);
        }

        return $this->respond(200, null);
    }
}
