<?php

namespace App\Http\Controllers\REST\V1\Manage\Products;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;
// Rule tidak lagi dibutuhkan di sini karena validasi dinamis dilakukan manual
// use Illuminate\Validation\Rule;

class Update extends BaseREST
{
    /**
     * Konstruktor yang benar sesuai dengan struktur kode.
     */
    public function __construct(?array $payload = [], ?array $file = [], ?array $auth = [])
    {
        $this->payload = $payload;
        $this->file = $file;
        $this->auth = $auth;
        return $this;
    }

    /**
     * Properti ini sekarang berisi SEMUA aturan validasi statis bawaan Laravel.
     * Aturan untuk 'variants.*.sku' sengaja DIHILANGKAN dari sini.
     * @var array
     */
    protected $payloadRules = [
        // Aturan untuk field produk utama
        'name' => 'sometimes|required|string|max:255',
        'brand' => 'sometimes|required|string|max:100',
        'description' => 'sometimes|nullable|string',
        'category_id' => 'sometimes|required|integer|exists:product_categories,id',
        'tags' => 'sometimes|nullable|array',

        // Aturan untuk array variants (tanpa SKU)
        'variants' => 'sometimes|array',
        'variants.*.price' => 'sometimes|required|numeric|min:0',
        'variants.*.specifications' => 'sometimes|nullable|array',

        // Aturan untuk manajemen gambar
        'new_images' => 'nullable|array',
        'new_images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        'deleted_image_ids' => 'nullable|array',
        'deleted_image_ids.*' => 'required|integer|exists:product_images,id',
        'cover_image_id' => 'nullable|integer|exists:product_images,id'
    ];

    protected $privilegeRules = [
        // 'EDIT_PRODUCT'
    ];

    /**
     * Metode utama hanya memanggil nextValidation().
     * @return \Illuminate\Http\JsonResponse
     */
    protected function mainActivity()
    {
        return $this->nextValidation();
    }

    /**
     * Menangani validasi bisnis dan validasi dinamis yang tidak bisa
     * ditaruh di $payloadRules.
     */
    private function nextValidation()
    {
        // Validasi Logika Bisnis #1: Pastikan produknya ada.
        if (!DBRepo::checkProductExists($this->payload['id'])) {
            return $this->error(404, ['reason' => 'Product not found']);
        }

        // --- VALIDASI DINAMIS UNTUK SKU ---
        // Ini adalah implementasi manual dari `Rule::unique()->ignore()`.
        if (isset($this->payload['variants'])) {
            foreach ($this->payload['variants'] as $variant) {
                // Hanya validasi jika SKU ada di payload varian
                if (isset($variant['sku'])) {
                    if (!DBRepo::isSkuAvailable($variant['sku'], $this->payload['id'])) {
                        // Kembalikan error 422 (Unprocessable Entity) untuk kesalahan validasi
                        return $this->error(422, [
                            'reason' => "The SKU '{$variant['sku']}' has already been taken."
                        ]);
                    }
                }
            }
        }

        // Jika semua validasi lolos, lanjutkan ke function executor.
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
            return $this->respond(200, $result->data);
        }

        return $this->error(500, ['reason' => $result->message]);
    }
}
