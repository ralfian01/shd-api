<?php

namespace App\Http\Controllers\REST\V1\Manage\ProductCategories;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;

class Insert extends BaseREST
{
    /**
     * Konstruktor standar sesuai template.
     */
    public function __construct(?array $p = [], ?array $f = [], ?array $a = [])
    {
        $this->payload = $p;
        $this->file = $f;
        $this->auth = $a;
        return $this;
    }

    /**
     * Aturan validasi untuk data yang masuk.
     * @var array
     */
    protected $payloadRules = [
        'name' => 'required|string|max:255|unique:product_categories,name',
        'slug' => 'required|string|max:255|unique:product_categories,slug|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        'description' => 'nullable|string',
        'parent_id' => 'nullable|integer|exists:product_categories,id' // Validasi 'exists' memastikan parent_id valid
    ];

    /**
     * Properti untuk aturan hak akses (privilege).
     * @var array
     */
    protected $privilegeRules = [
        // Contoh: 'CREATE_PRODUCT_CATEGORY'
    ];

    /**
     * Metode utama yang memulai aktivitas.
     * @return \Illuminate\Http\JsonResponse
     */
    protected function mainActivity()
    {
        return $this->nextValidation();
    }

    /**
     * Menangani langkah validasi bisnis selanjutnya.
     * Dalam kasus ini, tidak ada validasi tambahan yang diperlukan karena
     * aturan 'exists' sudah menangani validitas parent_id.
     */
    private function nextValidation()
    {
        return $this->insert();
    }

    /**
     * Function executor untuk menyisipkan data.
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
