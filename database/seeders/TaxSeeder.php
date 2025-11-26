<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxes = [
            [
                'business_id' => 1,
                'name' => 'PPN',
                'rate' => 11,
                'type' => 'PERCENTAGE'
            ],
            [
                'business_id' => 2,
                'name' => 'PPN',
                'rate' => 11,
                'type' => 'PERCENTAGE'
            ],
        ];

        foreach ($taxes as $member) {
            Tax::updateOrCreate(
                [
                    'business_id' => $member['business_id'],
                    'name' => $member['name'],
                    'rate' => $member['rate'],
                    'type' => $member['type'],
                ],
                $member
            );
        }
    }
}
