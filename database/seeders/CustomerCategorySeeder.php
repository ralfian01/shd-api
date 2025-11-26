<?php

namespace Database\Seeders;

use App\Models\CustomerCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customer = [
            [
                'business_id' => 1,
                'name' => 'Umum',
            ],
            [
                'business_id' => 1,
                'name' => 'VVIP',
            ],
            [
                'business_id' => 2,
                'name' => 'Umum',
            ],
            [
                'business_id' => 2,
                'name' => 'VVIP',
            ],
        ];

        foreach ($customer as $member) {
            CustomerCategory::updateOrCreate(
                [
                    'business_id' => $member['business_id'],
                    'name' => $member['name'],
                ],
                $member
            );
        }
    }
}
