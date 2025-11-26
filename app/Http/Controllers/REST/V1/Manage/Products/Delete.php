<?php

namespace App\Http\Controllers\REST\V1\Manage\Products;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;

class Delete extends BaseREST
{
    public function __construct(array $payload = [], array $file = [], array $auth = [])
    {
        parent::__construct($payload, $file, $auth);
    }

    // Tidak perlu payload rules, karena ID didapat dari URL
    protected $payloadRules = [];

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
            return $this->respond(['message' => 'Product successfully deleted'], 200);
        }

        return $this->error(500, ['reason' => $result->message]);
    }
}
