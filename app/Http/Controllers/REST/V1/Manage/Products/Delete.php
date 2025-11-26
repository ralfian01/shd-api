<?php

namespace App\Http\Controllers\REST\V1\Manage\Products;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;

class Delete extends BaseREST
{
    public function __construct(?array $p = [], ?array $f = [], ?array $a = [])
    {
        $this->payload = $p;
        $this->file = $f;
        $this->auth = $a;
        return $this;
    }

    // Tidak perlu payload rules, karena ID didapat dari URL
    protected $payloadRules = [
        'id' => 'required'
    ];

    protected function mainActivity()
    {
        return $this->nextValidation();
    }

    private function nextValidation()
    {
        // Pastikan produk yang akan dihapus ada
        if (!DBRepo::checkProductExists($this->payload['id'])) {
            return $this->error(404, ['reason' => 'Product not found']);
        }

        return $this->delete();
    }

    /**
     * Function executor to delete data
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $result = $dbRepo->deleteData();

        if ($result->status) {
            return $this->respond(200, ['message' => 'Product successfully deleted']);
        }

        return $this->error(500, ['reason' => $result->message]);
    }
}
