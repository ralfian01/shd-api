<?php

namespace App\Http\Controllers\REST\V1\Manage\Products;

use App\Http\Libraries\BaseDBRepo;
use App\Models\Product;
use App\Models\ProductCategory;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;


class DBRepo extends BaseDBRepo
{
    public function __construct(array $payload = [], array $file = [], array $auth = [])
    {
        parent::__construct($payload, $file, $auth);
    }

    /*
    |--------------------------------------------------------------------------
    | TOOLS - Static validation methods
    |--------------------------------------------------------------------------
    */
    public static function checkProductExists($id): bool
    {
        return Product::where('id', $id)->exists();
    }

    public static function checkCategoryIdExists($id): bool
    {
        return ProductCategory::where('id', $id)->exists();
    }

    /**
     * Function to get data from database
     * @return object
     */
    public function getData()
    {
        try {
            $query = Product::query()
                // Eager loading untuk mengambil semua data relasi dalam satu query
                ->with(['variants.sales.warranty']);

            // Filter by ID (untuk endpoint /manage/products/{id})
            if (isset($this->payload['id'])) {
                $product = $query->find($this->payload['id']);

                return (object) [
                    'status' => !is_null($product),
                    'data' => $product ? $product->toArray() : null,
                    'message' => $product ? 'Data found' : 'Product not found'
                ];
            }

            // Filter by keyword (untuk query param ?keyword=...)
            if (isset($this->payload['keyword'])) {
                $keyword = $this->payload['keyword'];
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'LIKE', "%{$keyword}%")
                        ->orWhere('brand', 'LIKE', "%{$keyword}%");
                });
            }

            // Filter by category_id (untuk query param ?category_id=...)
            if (isset($this->payload['category_id'])) {
                $query->where('category_id', $this->payload['category_id']);
            }

            $products = $query->paginate(15); // Menggunakan paginasi

            return (object) [
                'status' => true,
                'data' => $products->toArray(),
            ];
        } catch (Exception $e) {
            return (object) [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Function to insert data from database
     * @return object
     */
    public function insertData()
    {
        try {
            // Menggunakan transaction untuk memastikan semua data berhasil dibuat
            $productData = DB::transaction(function () {
                // 1. Buat produk utama
                $product = Product::create([
                    'name' => $this->payload['name'],
                    'brand' => $this->payload['brand'],
                    'description' => $this->payload['description'] ?? null,
                    'category_id' => $this->payload['category_id'],
                    'tags' => $this->payload['tags'] ?? null,
                ]);

                // 2. Loop dan buat varian produk
                foreach ($this->payload['variants'] as $variant) {
                    $product->variants()->create([
                        'sku' => $variant['sku'],
                        'price' => $variant['price'],
                        'specifications' => $variant['specifications'] ?? null,
                    ]);
                }

                if (isset($this->file['images'])) {
                    $coverIndex = $this->payload['cover_image_index'] ?? 0;

                    foreach ($this->file['images'] as $index => $imageFile) {
                        // Simpan file ke 'storage/app/public/products' dan dapatkan path-nya
                        $path = $imageFile->store('products', 'public');

                        $product->images()->create([
                            'path' => $path,
                            'is_cover' => ($index == $coverIndex)
                        ]);
                    }
                }

                // Muat ulang relasi varian setelah dibuat
                return $product->load('variants');
            });

            return (object) [
                'status' => true,
                'data' => $productData->toArray(),
            ];
        } catch (Exception $e) {
            return (object) ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Function to update data from database
     * @return object
     */
    public function updateData()
    {
        try {
            $productData = DB::transaction(function () {
                $product = Product::find($this->payload['id']);

                // 1. Update data produk utama jika ada
                $product->update(Arr::only($this->payload, ['name', 'brand', 'description', 'category_id', 'tags']));

                // 2. Jika ada data varian, hapus varian lama dan buat yang baru
                // Ini adalah pendekatan yang paling sederhana dan aman untuk API
                if (isset($this->payload['variants'])) {
                    $product->variants()->delete(); // Hapus semua varian yang ada

                    foreach ($this->payload['variants'] as $variant) {
                        $product->variants()->create([
                            'sku' => $variant['sku'],
                            'price' => $variant['price'],
                            'specifications' => $variant['specifications'] ?? null,
                        ]);
                    }
                }

                return $product->load('variants');
            });

            return (object) [
                'status' => true,
                'data' => $productData->toArray(),
            ];
        } catch (Exception $e) {
            return (object) ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Function to delete data from database
     * @return object
     */
    public function deleteData()
    {
        try {
            $product = Product::find($this->payload['id']);
            $product->delete(); // onDelete('cascade') akan menghapus varian dan relasi lainnya

            return (object) ['status' => true];
        } catch (Exception $e) {
            return (object) ['status' => false, 'message' => $e->getMessage()];
        }
    }
}
