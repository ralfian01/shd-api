<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Pcs', 'type' => 'QUANTITY', 'value_in_seconds' => null],
            ['name' => 'Jam', 'type' => 'TIME', 'value_in_seconds' => 3600],
            ['name' => 'Hari', 'type' => 'TIME', 'value_in_seconds' => 86400],
            ['name' => 'Pax', 'type' => 'QUANTITY', 'value_in_seconds' => null],
            ['name' => '15 Menit', 'type' => 'TIME', 'value_in_seconds' => 900],
        ];
        foreach ($units as $unit) {
            Unit::updateOrCreate(['name' => $unit['name']], $unit);
        }
    }
}
