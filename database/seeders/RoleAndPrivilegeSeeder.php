<?php

namespace Database\Seeders;

use App\Models\Privilege; // Sesuaikan path model
use App\Models\PrivilegeModel;
use App\Models\Role;      // Sesuaikan path model
use App\Models\RoleModel;
use Illuminate\Database\Seeder;

class RoleAndPrivilegeSeeder extends Seeder
{
    public function run(): void
    {
        // Definisikan peran dan hak aksesnya
        $rolesAndPrivileges = [
            'SUPER_ADMIN' => [
                'name' => 'Super Administrator',
                'privileges' => [
                    'MANAGE_PRODUCTS_VIEW',
                    'MANAGE_PRODUCTS_CREATE',
                    'MANAGE_PRODUCTS_UPDATE',
                    'MANAGE_PRODUCTS_DELETE',
                    'OPERATIONAL_CARTS_CREATE',
                    'OPERATIONAL_CHECKOUT',
                    'REPORTS_VIEW_SALES',
                ]
            ],
            'CASHIER' => [
                'name' => 'Kasir',
                'privileges' => [
                    'OPERATIONAL_CARTS_CREATE',
                    'OPERATIONAL_CHECKOUT',
                ]
            ],
        ];

        foreach ($rolesAndPrivileges as $roleCode => $roleData) {
            // Buat atau update peran
            $role = RoleModel::updateOrCreate(['code' => $roleCode], ['name' => $roleData['name']]);

            // Dapatkan ID dari privilege yang relevan
            $privilegeIds = PrivilegeModel::whereIn('code', $roleData['privileges'])->pluck('id');

            // Sinkronkan relasi di tabel pivot
            $role->rolePrivilege()->sync($privilegeIds);
        }
    }
}
