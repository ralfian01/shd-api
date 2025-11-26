<?php

namespace Database\Seeders;

use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Resource;
use App\Models\ResourceAvailability;
use App\Models\ServiceStock;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Ambil unit yang dibutuhkan agar seeder dinamis
            $unitJam = Unit::where('name', 'Jam')->firstOrFail();
            $unitHari = Unit::where('name', 'Hari')->firstOrFail();
            $unitPax = Unit::where('name', 'Pax')->firstOrFail();
            $unit9Holes = Unit::where('name', '9 Holes')->firstOrFail();
            $unitPcs = Unit::where('name', 'Pcs')->firstOrFail();

            // KASUS 1: TIME_SLOT -> Sewa Lapangan Badminton
            $prodBadminton = Product::updateOrCreate(
                ['name' => 'Sewa Lapangan Badminton'],
                ['product_type' => 'SERVICE', 'booking_mechanism' => 'TIME_SLOT']
            );
            $resourceBadminton = Resource::updateOrCreate(
                ['product_id' => $prodBadminton->product_id, 'name' => 'Lapangan Indoor A']
            );
            ResourceAvailability::updateOrCreate(
                ['resource_id' => $resourceBadminton->resource_id, 'day_of_week' => 1], // Senin
                ['start_time' => '08:00:00', 'end_time' => '22:00:00']
            );
            Pricing::updateOrCreate(
                ['product_id' => $prodBadminton->product_id, 'unit_id' => $unitJam->unit_id],
                ['price' => 85000]
            );

            // KASUS 2: TIME_SLOT_CAPACITY -> Sewa Gedung
            $prodGedung = Product::updateOrCreate(
                ['name' => 'Sewa Gedung Serbaguna'],
                ['product_type' => 'SERVICE', 'booking_mechanism' => 'TIME_SLOT_CAPACITY']
            );
            $resourceGedung = Resource::updateOrCreate(
                ['product_id' => $prodGedung->product_id, 'name' => 'Aula Serbaguna'],
                ['capacity' => 200]
            );
            Pricing::updateOrCreate(
                ['product_id' => $prodGedung->product_id, 'unit_id' => $unitHari->unit_id],
                ['price' => 5000000]
            );
            Pricing::updateOrCreate(
                ['product_id' => $prodGedung->product_id, 'unit_id' => $unitPax->unit_id],
                ['price' => 75000, 'name' => 'Harga per Pax (termasuk catering)']
            );

            // KASUS 3: CONSUMABLE_STOCK -> Main Golf
            $prodGolf = Product::updateOrCreate(
                ['name' => 'Main Golf 9 Holes'],
                ['product_type' => 'SERVICE', 'booking_mechanism' => 'CONSUMABLE_STOCK']
            );
            Pricing::updateOrCreate(
                ['product_id' => $prodGolf->product_id, 'unit_id' => $unit9Holes->unit_id],
                ['price' => 450000]
            );
            ServiceStock::updateOrCreate(
                ['product_id' => $prodGolf->product_id, 'unit_id' => $unit9Holes->unit_id],
                ['name' => 'Weekday 9 Holes Session', 'available_quantity' => 50]
            );

            // KASUS 4: INVENTORY_STOCK -> Kaos Polo
            $prodKaos = Product::updateOrCreate(
                ['name' => 'Kaos Polo Official'],
                ['product_type' => 'GOODS', 'booking_mechanism' => 'INVENTORY_STOCK']
            );
            $variantKaosL = ProductVariant::updateOrCreate(
                ['product_id' => $prodKaos->product_id, 'name' => 'Ukuran L'],
                ['stock_quantity' => 100, 'sku' => 'KPO-L']
            );
            Pricing::updateOrCreate(
                ['product_id' => $prodKaos->product_id, 'variant_id' => $variantKaosL->variant_id, 'unit_id' => $unitPcs->unit_id],
                ['price' => 250000]
            );
        });
    }
}
