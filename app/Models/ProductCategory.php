<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];


    /**
     * Mendapatkan semua produk dalam kategori ini.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * Mendapatkan kategori induk (jika ada).
     */
    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    /**
     * Mendapatkan semua sub-kategori.
     */
    public function children()
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }
}
