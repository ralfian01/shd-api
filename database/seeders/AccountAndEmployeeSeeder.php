<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountModel;
use App\Models\Employee;
use App\Models\Outlet;
use App\Models\Role;
use App\Models\RoleModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountAndEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil data induk
        $adminRole = RoleModel::where('code', 'SUPER_ADMIN')->firstOrFail();
        $cashierRole = RoleModel::where('code', 'CASHIER')->firstOrFail();
        $firstOutlet = Outlet::first(); // Asumsi outlet sudah ada dari seeder lain

        // --- Buat Akun & Karyawan Admin ---
        $adminAccount = AccountModel::updateOrCreate(
            ['username' => 'admin'],
            [
                'uuid' => Str::uuid(),
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'status_active' => true
            ]
        );

        $adminEmployee = Employee::updateOrCreate(
            ['account_id' => $adminAccount->id],
            [
                'name' => 'Super Administrator',
                'phone_number' => '081000000001',
                'is_active' => true,
            ]
        );

        // --- Buat Akun & Karyawan Kasir ---
        $cashierAccount = AccountModel::updateOrCreate(
            ['username' => 'kasir01'],
            [
                'uuid' => Str::uuid(),
                'password' => Hash::make('password'),
                'role_id' => $cashierRole->id,
                'status_active' => true
            ]
        );

        $cashierEmployee = Employee::updateOrCreate(
            ['account_id' => $cashierAccount->id],
            [
                'name' => 'Budi (Kasir)',
                'phone_number' => '081000000002',
                'is_active' => true,
            ]
        );

        // Assign karyawan ke outlet jika outlet ada
        if ($firstOutlet) {
            $adminEmployee->outlets()->sync([$firstOutlet->id]);
            $cashierEmployee->outlets()->sync([$firstOutlet->id]);
        }
    }
}
