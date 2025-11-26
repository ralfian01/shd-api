<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customer = [
            [
                'business_id' => 1,
                'customer_category_id' => 1,
                'name' => 'Ahmad',
                'phone_number' => '081234356789',
            ],
            [
                'business_id' => 1,
                'customer_category_id' => 2,
                'name' => 'Iman',
                'phone_number' => '081234356780',
            ],
        ];

        foreach ($customer as $member) {
            Customer::updateOrCreate(
                [
                    'business_id' => $member['business_id'],
                    'customer_category_id' => $member['customer_category_id'],
                    'name' => $member['name'],
                    'phone_number' => $member['phone_number'],
                ],
                $member
            );
        }
    }
}
