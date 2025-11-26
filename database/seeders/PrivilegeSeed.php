<?php

namespace Database\Seeders;

use App\Models\PrivilegeModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrivilegeSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $privileges = [
            ['code' => 'ACCOUNT_MANAGE_VIEW', 'description' => 'View account list'],
            ['code' => 'ACCOUNT_MANAGE_ADD', 'description' => 'Add or delete account'],
            ['code' => 'ACCOUNT_MANAGE_MODIFY', 'description' => 'Modify account'],
            ['code' => 'ACCOUNT_MANAGE_SUSPEND', 'description' => 'Suspend or activate account'],
            ['code' => 'ACCOUNT_MANAGE_PRIVILEGE', 'description' => 'Set account privileges'],

            ['code' => 'ADMIN_MANAGE_ADD', 'description' => 'Add or delete admin'],
            ['code' => 'ADMIN_MANAGE_VIEW', 'description' => 'View admin list'],
            ['code' => 'ADMIN_MANAGE_SUSPEND', 'description' => 'Suspend or activate admin'],
            ['code' => 'ADMIN_MANAGE_PRIVILEGE', 'description' => 'Set admin privileges'],

            ['code' => 'POSITION_MANAGE_VIEW', 'description' => 'Manager add or delete position'],
            ['code' => 'POSITION_MANAGE_ADD', 'description' => 'Manager view position'],
            ['code' => 'POSITION_MANAGE_MODIFY', 'description' => 'Manager modify position'],

            ['code' => 'SYSTEM_OPT_MANAGE_VIEW', 'description' => 'Manager add or delete system options'],
            ['code' => 'SYSTEM_OPT_MANAGE_ADD', 'description' => 'Manager view system options'],
            ['code' => 'SYSTEM_OPT_MANAGE_MODIFY', 'description' => 'Manager modify system options'],

            ['code' => 'IN_MAIL_MANAGE_VIEW', 'description' => 'Manager add or delete incoming mail'],
            ['code' => 'IN_MAIL_MANAGE_ADD', 'description' => 'Manager view incoming mail'],
            ['code' => 'IN_MAIL_MANAGE_MODIFY', 'description' => 'Manager modify incoming mail'],

            ['code' => 'OUT_MAIL_MANAGE_VIEW', 'description' => 'Manager add or delete outcoming mail'],
            ['code' => 'OUT_MAIL_MANAGE_ADD', 'description' => 'Manager view outcoming mail'],
            ['code' => 'OUT_MAIL_MANAGE_MODIFY', 'description' => 'Manager modify outcoming mail'],

            // Manajemen Produk
            ['code' => 'MANAGE_PRODUCTS_VIEW', 'description' => 'Melihat daftar produk'],
            ['code' => 'MANAGE_PRODUCTS_CREATE', 'description' => 'Menambah produk baru'],
            ['code' => 'MANAGE_PRODUCTS_UPDATE', 'description' => 'Mengedit produk'],
            ['code' => 'MANAGE_PRODUCTS_DELETE', 'description' => 'Menghapus produk'],

            // Operasional Kasir
            ['code' => 'OPERATIONAL_CARTS_CREATE', 'description' => 'Membuat sesi keranjang baru'],
            ['code' => 'OPERATIONAL_CHECKOUT', 'description' => 'Melakukan checkout pembayaran'],

            // Laporan
            ['code' => 'REPORTS_VIEW_SALES', 'description' => 'Melihat laporan penjualan'],
        ];

        foreach ($privileges as $privilege) {
            PrivilegeModel::updateOrCreate(['code' => $privilege['code']], $privilege);
        }
    }
}
