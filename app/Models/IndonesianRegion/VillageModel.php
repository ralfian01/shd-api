<?php

namespace App\Models\IndonesianRegion;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 */
class VillageModel extends Model
{
    use HasFactory;

    const CREATED_AT = 'tivl_createdAt';
    const UPDATED_AT = 'tivl_updatedAt';

    protected $primaryKey = 'tivl_id';
    protected $table = 'indonesian__village';
    protected $fillable = [
        'tidt_id',
        'tivl_name',
        'tivl_meta',
    ];
    protected $hidden = [
        'tivl_createdAt',
        'tivl_updatedAt',
    ];

    /**
     * Relation with table district
     */
    public function dictrict()
    {
        return $this->belongsTo(DistrictModel::class, 'tidt_id');
    }
}
