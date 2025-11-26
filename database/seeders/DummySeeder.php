<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            BusinessSeeder::class,
            OutletSeeder::class,
            CustomerCategorySeeder::class,
            CustomerSeeder::class,
            MemberSeeder::class,
            ProductCategorySeeder::class,
            TaxSeeder::class,
            PrivilegeSeed::class,
            RoleAndPrivilegeSeeder::class,
            AccountAndEmployeeSeeder::class
        ]);
    }
}
