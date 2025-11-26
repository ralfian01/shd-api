<?php

namespace App\Http\Controllers\REST\V1\Manage\ProductCategories;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;
use Illuminate\Validation\Rule;

class Update extends BaseREST
{
    /**
     * Konstruktor standar sesuai template.
     */
    public function __construct(array $payload = [], ?array $file = [], ?array $auth = [])
    {
        parent::__construct($payload, $file, $auth);
    }

    /**
     * Aturan validasi untuk data yang masuk. Didefinisikan di sini tapi diinisialisasi
     * secara dinamis di mainActivity karena butuh {id}.
     * @var array
     */
    protected $payloadRules = [];

    /**
     * Properti untuk aturan hak akses (privilege).
     * @var array
     */
    protected $privilegeRules = [
        // Contoh: 'EDIT_PRODUCT_CATEGORY'
    ];

    /**
     * Metode utama yang memulai aktivitas.
     * @return \Illuminate\Http\JsonResponse
     */
    protected function mainActivity()
    {
        // Aturan validasi didefinisikan di sini karena Rule::unique() memerlukan ID
        // dari payload yang baru tersedia di dalam method.
        $categoryId = $this->payload['id'] ?? null;
        $this->payloadRules = [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('product_categories')->ignore($categoryId)],
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('product_categories')->ignore($categoryId)],
            'description' => 'sometimes|nullable|string',
            'parent_id' => 'sometimes|nullable|integer|exists:product_categories,id'
        ];

        return $this->nextValidation();
    }

    /**
     * Menangani langkah validasi bisnis selanjutnya.
     * Wajib memeriksa apakah kategori yang akan di-update memang ada.
     */
    private function nextValidation()
    {
        if (!DBRepo::checkCategoryExists($this->payload['id'])) {
            return $this->error(404, ['reason' => 'Category not found']);
        }

        return $this->update();
    }

    /**
     * Function executor untuk memperbarui data.
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
