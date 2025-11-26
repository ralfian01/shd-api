<?php

namespace App\Http\Controllers\REST\V1\Manage\Products;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors; // Asumsi ada kelas Errors

class Get extends BaseREST
{
    public function __construct(?array $p = [], ?array $f = [], ?array $a = [])
    {
        $this->payload = $p;
        $this->file = $f;
        $this->auth = $a;
        return $this;
    }

    protected $payloadRules = [
        'keyword' => 'sometimes|string|max:100',
        'category_id' => 'sometimes|integer',
    ];

    protected $privilegeRules = [];

    protected function mainActivity()
    {
        return $this->nextValidation();
    }

    private function nextValidation()
    {
        // Untuk GET, bisa langsung memanggil executor
        return $this->get();
    }

    /**
     * Function executor untuk mengambil data
     * @return \Illuminate\Http\JsonResponse
     */
    public function get()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $result = $dbRepo->getData();

        if ($result->status) {
            return $this->respond(200, $result->data);
        }

        // Contoh penanganan error
        return $this->error(500, ['reason' => $result->message]);
    }
}
