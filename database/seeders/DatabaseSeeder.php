<?php

use Database\Seeders\PaymentMethodSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UnitSeeder::class,
            PaymentMethodSeeder::class,
            // ProductSeeder::class, // Pastikan ProductSeeder dipanggil setelah UnitSeeder
            // Seeder lain yang mungkin Anda miliki
        ]);
    }
}
