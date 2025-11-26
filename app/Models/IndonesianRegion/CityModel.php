<?php

namespace App\Models\IndonesianRegion;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 */
class CityModel extends Model
{
    use HasFactory;

    const CREATED_AT = 'tict_createdAt';
    const UPDATED_AT = 'tict_updatedAt';

    protected $primaryKey = 'tict_id';
    protected $table = 'indonesian__city';
    protected $fillable = [
        'tipr_id',
        'tict_name',
        'tict_meta',
    ];
    protected $hidden = [
        'tict_createdAt',
        'tict_updatedAt',
    ];

    /**
     * Relation with table province
     */
    public function province()
    {
        return $this->belongsTo(ProvinceModel::class, 'tipr_id');
    }
}
