<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $product = [
            [
                'business_id' => 1,
                'name' => 'Sewa Harian',
            ],
            [
                'business_id' => 1,
                'name' => 'Sewa Per Jam',
            ],
            [
                'business_id' => 2,
                'name' => 'Driving Range',
            ],
        ];

        foreach ($product as $member) {
            ProductCategory::updateOrCreate(
                [
                    'business_id' => $member['business_id'],
                    'name' => $member['name'],
                ],
                $member
            );
        }
    }
}
