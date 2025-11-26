<?php

namespace App\Http\Controllers\REST\V1\Manage\Products;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;
use Illuminate\Validation\Rule;

class Update extends BaseREST
{
    public function __construct(array $payload = [], array $file = [], array $auth = [])
    {
        parent::__construct($payload, $file, $auth);
    }

    // Untuk PUT, kita gunakan 'sometimes' agar hanya field yang dikirim yang divalidasi
    protected $payloadRules = [
        'name' => 'sometimes|required|string|max:255',
        'brand' => 'sometimes|required|string|max:100',
        'description' => 'sometimes|nullable|string',
        'category_id' => 'sometimes|required|integer',
        'tags' => 'sometimes|nullable|array',
        'variants' => 'sometimes|required|array|min:1',
        'variants.*.sku' => [
            'sometimes',
            'required',
            'string',
            // Pastikan SKU unik, kecuali untuk SKU yang sudah dimiliki produk ini
            Rule::unique('variants', 'sku')->ignore($this->payload['id'] ?? null, 'product_id'),
        ],
        'variants.*.price' => 'sometimes|required|numeric|min:0',
        'variants.*.specifications' => 'sometimes|nullable|array',
    ];

    protected function mainActivity()
    {
        return $this->nextValidation();
    }

    private function nextValidation()
    {
        // Pastikan produk yang akan diupdate ada
        if (!DBRepo::checkProductExists($this->payload['id'])) {
            return $this->error(404, ['reason' => 'Product not found']);
        }

        // Jika category_id dikirim, pastikan ada
        if (isset($this->payload['category_id'])) {
            if (!DBRepo::checkCategoryIdExists($this->payload['category_id'])) {
                return $this->error(404, ['reason' => 'Category not found']);
            }
        }

        return $this->update();
    }

    /**
     * Function executor to update data
     * @return \Illuminate\Http\JsonResponse
     */
    public function update()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $result = $dbRepo->updateData();

        if ($result->status) {
            return $this->respond($result->data);
        }

        return $this->error(500, ['reason' => $result->message]);
    }
}
