<?php

namespace App\Models\IndonesianRegion;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 */
class DistrictModel extends Model
{
    use HasFactory;

    const CREATED_AT = 'tidt_createdAt';
    const UPDATED_AT = 'tidt_updatedAt';

    protected $primaryKey = 'tidt_id';
    protected $table = 'indonesian__district';
    protected $fillable = [
        'tict_id',
        'tidt_name',
        'tidt_meta',
    ];
    protected $hidden = [
        'tidt_createdAt',
        'tidt_updatedAt',
    ];

    /**
     * Relation with table city
     */
    public function city()
    {
        return $this->belongsTo(CityModel::class, 'tict_id');
    }
}
