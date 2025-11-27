<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];


    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function warranties()
    {
        return $this->hasMany(Warranty::class);
    }
}
