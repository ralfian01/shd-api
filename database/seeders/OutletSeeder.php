<?php

namespace Database\Seeders;

use App\Models\Outlet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OutletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $business = [
            [
                'business_id' => 1,
                'name' => 'SG - Pondok Indah',
                'is_active' => true,
            ],
            [
                'business_id' => 2,
                'name' => 'PG - Pondok Indah',
                'is_active' => true,
            ],
            [
                'business_id' => 2,
                'name' => 'PG - Pantai Indah Kapuk',
                'is_active' => true,
            ],
        ];

        foreach ($business as $member) {
            Outlet::updateOrCreate(
                [
                    'business_id' => $member['business_id'],
                    'name' => $member['name'],
                    'is_active' => $member['is_active'],
                ],
                $member
            );
        }
    }
}
