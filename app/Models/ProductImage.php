<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];


    // Accessor untuk mendapatkan URL lengkap ke file
    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
