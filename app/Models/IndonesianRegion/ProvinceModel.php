<?php

namespace App\Models\IndonesianRegion;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 */
class ProvinceModel extends Model
{
    use HasFactory;

    const CREATED_AT = 'tipr_createdAt';
    const UPDATED_AT = 'tipr_updatedAt';

    protected $primaryKey = 'tipr_id';
    protected $table = 'indonesian__province';
    protected $fillable = [
        'tipr_name',
        'tipr_meta',
    ];
    protected $hidden = [
        'tipr_createdAt',
        'tipr_updatedAt',
    ];
}
