<?php

namespace Database\Seeders;

use App\Models\Business;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $business = [
            [
                'name' => 'Sewa Gedung',
                'contact' => '08123456789',
                'is_active' => true,
            ],
            [
                'name' => 'Padang Golf',
                'contact' => '08123456789',
                'is_active' => true,
            ],
        ];

        foreach ($business as $member) {
            Business::updateOrCreate(
                [
                    'name' => $member['name'],
                    'contact' => $member['contact'],
                    'is_active' => $member['is_active'],
                ],
                $member
            );
        }
    }
}
