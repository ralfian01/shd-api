<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Hash;

/**
 * @method static mixed getWithPrivileges() Get account with its privileges
 * @method mixed getWithPrivileges() Get account with its privileges
 */
class AccountModel extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $table = 'account';
    protected $fillable = [
        'uuid',
        'username',
        'password',
        'status_active',
        'status_delete',
    ];
    protected $hidden = [
        'password',
        'created_at',
        'updated_at'
    ];

    /**
     * Relation with table role
     */
    public function accountRole()
    {
        return $this->belongsTo(RoleModel::class, 'role_id');
    }

    /**
     * Privilege from relation between account, account__privilege and privilege tables
     */
    public function accountPrivilege()
    {
        return $this->belongsToMany(PrivilegeModel::class, 'account__privilege', 'id', 'privilege_id');
    }

    /**
     * KUNCI PERBAIKAN: Mendefinisikan relasi many-to-many ke Role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            RoleModel::class,        // Model terkait
            'role_account',   // Nama tabel pivot
            'account_id',     // Foreign key dari model INI (Account) di tabel pivot
            'role_id'         // Foreign key dari model TERKAIT (Role) di tabel pivot
        );
    }

    /**
     * Mutator untuk secara otomatis mengenkripsi (hash) password
     * setiap kali nilainya diatur.
     */
    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn($value) => Hash::make($value),
        );
    }

    /**
     * Get account with its privileges
     */
    protected function scopeGetWithPrivileges(Builder $query)
    {
        return $query
            ->with(['accountPrivilege', 'roles.rolePrivilege'])
            ->addSelect(['id'])
            ->get()
            ->map(function ($acc) {

                $acc->makeHidden(['accountPrivilege', 'roles']);

                if (isset($acc->accountPrivilege)) {
                    $acc->privileges = $acc->accountPrivilege->map(function ($prv) {
                        return $prv->code;
                    })->toArray();
                }

                if (isset($acc->roles)) {
                    $acc->privileges = $acc->roles->map(function ($rov) {
                        return $rov->rolePrivilege->map(function ($prv) {
                            return $prv->code;
                        })->toArray();
                    })->first();
                }

                $acc->privileges = array_unique($acc->privileges);
                return $acc;
            });
    }
}
