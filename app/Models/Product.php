<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }
    
    // --- TAMBAHAN DI SINI ---
    /**
     * Mendapatkan kategori dari produk ini.
     */
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * Mendapatkan semua gambar untuk produk ini.
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * Relasi helper untuk mendapatkan gambar cover dengan mudah.
     */
    public function coverImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_cover', true);
    }
}
