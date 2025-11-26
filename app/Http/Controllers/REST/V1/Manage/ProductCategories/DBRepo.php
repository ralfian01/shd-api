<?php

namespace App\Http\Controllers\REST\V1\Manage\ProductCategories;

use App\Http\Libraries\BaseDBRepo;
use App\Models\ProductCategory;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

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
    public static function checkCategoryExists($id): bool
    {
        return ProductCategory::where('id', $id)->exists();
    }

    public static function checkHasProducts($id): bool
    {
        $category = ProductCategory::find($id);
        return $category ? $category->products()->exists() : false;
    }

    /*
    |--------------------------------------------------------------------------
    | DATABASE TRANSACTION
    |--------------------------------------------------------------------------
    */
    public function getData()
    {
        try {
            $query = ProductCategory::query()
                ->with(['parent', 'children'])
                ->withCount('products');

            // Handle request untuk satu kategori by ID
            if (isset($this->payload['id'])) {
                $category = $query->find($this->payload['id']);
                return (object) [
                    'status' => !is_null($category),
                    'data' => $category ? $category->toArray() : null,
                    'message' => $category ? 'Data found' : 'Category not found'
                ];
            }

            // --- PERBAIKAN DI SINI ---
            // Menambahkan logika filter berdasarkan keyword dari payload
            if (isset($this->payload['keyword'])) {
                $keyword = $this->payload['keyword'];
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'LIKE', "%{$keyword}%")
                        ->orWhere('description', 'LIKE', "%{$keyword}%");
                });
            }

            $categories = $query->paginate(15);

            return (object) ['status' => true, 'data' => $categories->toArray()];
        } catch (Exception $e) {
            return (object) ['status' => false, 'message' => $e->getMessage()];
        }
    }


    public function insertData()
    {
        try {
            $category = ProductCategory::create(Arr::only($this->payload, [
                'name',
                'slug',
                'description',
                'parent_id'
            ]));

            return (object) ['status' => true, 'data' => $category->toArray()];
        } catch (Exception $e) {
            return (object) ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateData()
    {
        try {
            $category = ProductCategory::find($this->payload['id']);
            $category->update(Arr::only($this->payload, [
                'name',
                'slug',
                'description',
                'parent_id'
            ]));

            return (object) ['status' => true, 'data' => $category->toArray()];
        } catch (Exception $e) {
            return (object) ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteData()
    {
        try {
            $category = ProductCategory::find($this->payload['id']);
            $category->delete();

            return (object) ['status' => true];
        } catch (Exception $e) {
            return (object) ['status' => false, 'message' => $e->getMessage()];
        }
    }
}
