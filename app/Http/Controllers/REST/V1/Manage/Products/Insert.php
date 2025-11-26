<?php

namespace App\Http\Controllers\REST\V1\Manage\Products;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;

class Insert extends BaseREST
{
    public function __construct(?array $p = [], ?array $f = [], ?array $a = [])
    {
        $this->payload = $p;
        $this->file = $f;
        $this->auth = $a;
        return $this;
    }

    /**
     * @var array Property that contains the payload rules
     */
    protected $payloadRules = [
        'name' => 'required|string|max:255',
        'brand' => 'required|string|max:100',
        'description' => 'nullable|string',
        'category_id' => 'required|integer',
        'tags' => 'nullable|array',
        'variants' => 'required|array|min:1',
        'warranty_duration_months' => 'required|integer|min:0',
        // Validasi untuk setiap item di dalam array variants
        'variants.*.sku' => 'required|string|unique:variants,sku',
        'variants.*.price' => 'required|numeric|min:0',
        'variants.*.specifications' => 'nullable|array',

        'images' => 'nullable|array', // 'images' adalah array file
        'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048', // Setiap file harus gambar maks 2MB
        'cover_image_index' => 'nullable|integer' // Index dari array 'images' yang akan jadi cover
    ];

    /**
     * The method that starts the main activity
     * @return \Illuminate\Http\JsonResponse
     */
    protected function mainActivity()
    {
        return $this->nextValidation();
    }

    /**
     * Handle the next step of payload validation
     */
    private function nextValidation()
    {
        // Pastikan category_id yang dikirim ada di database
        if (!DBRepo::checkCategoryIdExists($this->payload['category_id'])) {
            return $this->error(404, ['reason' => 'Category not found']);
        }

        return $this->insert();
    }

    /**
     * Function executor to insert data
     * @return \Illuminate\Http\JsonResponse
     */
    public function insert()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $result = $dbRepo->insertData();

        if ($result->status) {
            return $this->respond(201, $result->data); // 201 Created
        }

        return $this->error(500, ['reason' => $result->message]);
    }
}
