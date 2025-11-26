<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warranty extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast.
     * Ini akan secara otomatis mengubah kolom menjadi tipe data yang sesuai.
     *
     * @var array
     */
    protected $casts = [
        // --- TAMBAHAN DI SINI ---
        'voided_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
