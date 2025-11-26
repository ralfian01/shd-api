<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Cash',
                'type' => 'CASH',
                'description' => 'Pembayaran tunai.',
                'is_active' => true,
            ],
            [
                'name' => 'EDC BCA',
                'type' => 'EDC',
                'description' => 'Pembayaran via mesin EDC Bank Central Asia.',
                'is_active' => true,
            ],
            [
                'name' => 'QRIS',
                'type' => 'QRIS',
                'description' => 'Pembayaran via QRIS (Gopay, OVO, Dana, dll).',
                'is_active' => true,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['name' => $method['name']], // Key untuk mencari
                $method // Data untuk di-create atau di-update
            );
        }
    }
}
