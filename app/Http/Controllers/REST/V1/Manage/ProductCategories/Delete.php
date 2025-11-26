<?php

namespace App\Http\Controllers\REST\V1\Manage\ProductCategories;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;

class Delete extends BaseREST
{
    /**
     * Konstruktor standar sesuai template.
     */
    public function __construct(array $payload = [], ?array $file = [], ?array $auth = [])
    {
        parent::__construct($payload, $file, $auth);
    }

    /**
     * Tidak ada aturan payload karena ID berasal dari URL.
     * @var array
     */
    protected $payloadRules = [];

    /**
     * Properti untuk aturan hak akses (privilege).
     * @var array
     */
    protected $privilegeRules = [
        // Contoh: 'DELETE_PRODUCT_CATEGORY'
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
     * Ini adalah langkah krusial untuk operasi DELETE.
     */
    private function nextValidation()
    {
        // 1. Periksa apakah kategori yang akan dihapus ada.
        if (!DBRepo::checkCategoryExists($this->payload['id'])) {
            return $this->error(404, ['reason' => 'Category not found']);
        }

        // 2. Periksa apakah ada produk yang terkait dengan kategori ini.
        if (DBRepo::checkHasProducts($this->payload['id'])) {
            return $this->error(409, ['reason' => 'Cannot delete. This category is currently in use by one or more products.']);
        }

        // Jika semua validasi lolos, lanjutkan ke penghapusan.
        return $this->delete();
    }

    /**
     * Function executor untuk menghapus data.
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $result = $dbRepo->deleteData();

        if ($result->status) {
            return $this->respond(['message' => 'Category successfully deleted.']);
        }

        return $this->error(500, ['reason' => $result->message]);
    }
}
