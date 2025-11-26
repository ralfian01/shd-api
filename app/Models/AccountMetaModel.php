<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 */
class AccountMetaModel extends Model
{
    use HasFactory;

    const CREATED_AT = 'tam_createdAt';
    const UPDATED_AT = 'tam_updatedAt';

    protected $primaryKey = 'tam_id';
    protected $table = 'account__meta';
    protected $fillable = [
        'ta_id',
        'tam_code',
        'tam_value',
    ];
    protected $hidden = [
        'tam_createdAt',
        'tam_updatedAt',
    ];

    /**
     * Relation with table account
     */
    public function account()
    {
        return $this->belongsTo(AccountModel::class, 'ta_id');
    }
}
