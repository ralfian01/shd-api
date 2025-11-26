<?php

namespace App\Http\Controllers\REST\V1\Manage\Products;

use App\Http\Libraries\BaseDBRepo;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;



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

    public static function isSkuAvailable(string $sku, int $productIdToIgnore): bool
    {
        return !DB::table('variants')
            ->where('sku', $sku)
            ->where('product_id', '!=', $productIdToIgnore)
            ->exists();
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
                ->with([
                    'variants',
                    'images',
                    'category'
                ]);

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
            return DB::transaction(function () {
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
                        // Ganti panggilan ->store() dengan helper method manual kita
                        $path = $this->handleFileUpload($imageFile, 'products');

                        $product->images()->create([
                            'path' => $path,
                            'is_cover' => ($index == $coverIndex)
                        ]);
                    }
                }

                return (object) [
                    'status' => true,
                    'data' => ['id' => $product->id]
                ];
            });
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
            // DB::transaction akan secara otomatis melakukan commit jika berhasil,
            // atau rollback jika terjadi Exception.
            return DB::transaction(function () {
                // Langkah 1: Temukan produk yang akan diupdate.
                $product = Product::find($this->payload['id']);

                // Langkah 2: Update detail inti produk.
                // Menggunakan Arr::only untuk keamanan, hanya memperbarui field yang diizinkan.
                $product->update(Arr::only($this->payload, [
                    'name',
                    'brand',
                    'description',
                    'category_id',
                    'tags'
                ]));

                // Langkah 3: Update varian produk dengan pendekatan "delete-then-create".
                // Ini adalah cara paling sederhana dan aman untuk API.
                if (isset($this->payload['variants'])) {
                    // Hapus semua varian lama yang terkait dengan produk ini.
                    $product->variants()->delete();

                    // Buat ulang varian dari data payload yang baru.
                    foreach ($this->payload['variants'] as $variant) {
                        $product->variants()->create([
                            'sku' => $variant['sku'],
                            'price' => $variant['price'],
                            'specifications' => $variant['specifications'] ?? null,
                        ]);
                    }
                }

                // Langkah 4: Kelola gambar produk.

                // 4a. Hapus gambar yang diminta untuk dihapus.
                if (isset($this->payload['deleted_image_ids'])) {
                    $imagesToDelete = ProductImage::whereIn('id', $this->payload['deleted_image_ids'])
                        ->where('product_id', $product->id) // Keamanan ekstra
                        ->get();

                    foreach ($imagesToDelete as $image) {
                        // Hapus file fisik dari storage.
                        Storage::disk('public')->delete($image->path);
                        // Hapus record dari database.
                        $image->delete();
                    }
                }

                // 4b. Tambahkan gambar baru yang di-upload.
                if (isset($this->file['new_images'])) {
                    foreach ($this->file['new_images'] as $imageFile) {
                        // Gunakan helper manual untuk menangani objek file Symfony.
                        $path = $this->handleFileUpload($imageFile, 'products');
                        $product->images()->create(['path' => $path]);
                    }
                }

                // 4c. Atur gambar cover baru jika diminta.
                if (isset($this->payload['cover_image_id'])) {
                    // Reset semua gambar untuk produk ini agar tidak menjadi cover.
                    $product->images()->update(['is_cover' => false]);
                    // Set satu gambar spesifik sebagai cover baru.
                    ProductImage::where('id', $this->payload['cover_image_id'])
                        ->where('product_id', $product->id) // Keamanan ekstra
                        ->update(['is_cover' => true]);
                }

                // Langkah 5: Muat ulang relasi yang mungkin telah berubah
                // untuk memastikan response API berisi data terbaru.
                return (object) [
                    'status' => true,
                    'data' => ['id' => $product->id]
                ];
            });
        } catch (Exception $e) {
            return (object) [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
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

    /**
     * --- HELPER METHOD BARU ---
     * Method ini secara manual menangani upload file dari objek Symfony UploadedFile.
     * Ini adalah pengganti dari method ->store() milik Laravel.
     *
     * @param UploadedFile $file Objek file dari Symfony.
     * @param string $directory Sub-direktori di dalam 'storage/app/public'.
     * @return string Path relatif dari file yang disimpan untuk database.
     */
    private function handleFileUpload(UploadedFile $file, string $directory): string
    {
        // 1. Buat nama file yang unik untuk menghindari konflik.
        $extension = $file->getClientOriginalExtension();
        $newFilename = uniqid() . '_' . time() . '.' . $extension;

        // 2. Tentukan path tujuan absolut di server.
        // storage_path() adalah helper Laravel untuk mendapatkan path ke direktori storage.
        $destinationPath = storage_path('app/public/' . $directory);

        // 3. Pindahkan file dari lokasi temporary ke lokasi permanen.
        // Ini adalah method inti dari objek Symfony UploadedFile.
        $file->move($destinationPath, $newFilename);

        // 4. Kembalikan path relatif (tanpa 'storage/app/public') untuk disimpan di database.
        // Ini adalah format yang sama yang dihasilkan oleh method ->store().
        return $directory . '/' . $newFilename;
    }
}
