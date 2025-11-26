<?php

namespace App\Models;

use App\Models\IndonesianRegion\CityModel;
use App\Models\IndonesianRegion\ProvinceModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static mixed getWithComplete() Get valid region with region name
 * @method mixed getWithComplete() Get valid region with region name
 */
class ValidRegionModel extends Model
{
    use HasFactory;

    const CREATED_AT = 'tvr_createdAt';
    const UPDATED_AT = 'tvr_updatedAt';

    protected $primaryKey = 'tvr_id';
    protected $table = 'valid_region';
    protected $fillable = [
        'tipr_id',
        'tict_id',
    ];
    protected $hidden = [
        'tvr_createdAt',
        'tvr_updatedAt',
    ];

    /**
     * Relation with table province
     */
    public function province()
    {
        return $this->belongsTo(ProvinceModel::class, 'tipr_id');
    }

    /**
     * Relation with table city
     */
    public function city()
    {
        return $this->belongsTo(CityModel::class, 'tict_id');
    }

    /**
     * Get account with region name
     */
    protected function scopeGetWithComplete(Builder $query)
    {
        return $query
            ->with(['province', 'city'])
            ->addSelect(['tipr_id', 'tict_id'])
            ->get()
            ->map(function ($acc) {

                $acc->makeHidden(['province', 'city']);
                $acc->province_name = $acc->city->tipr_name;
                $acc->city_name = $acc->city->tict_name;

                return $acc;
            });
    }
}
