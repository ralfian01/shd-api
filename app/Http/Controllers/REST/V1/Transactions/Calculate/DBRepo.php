<?php

namespace App\Http\Controllers\REST\V1\Transactions\Calculate;

use App\Http\Libraries\BaseDBRepo;
use App\Models\ActiveSession;
use App\Models\Booking;
use App\Models\Pricing;
use App\Models\ProductVariant;
use App\Models\Resource;
use App\Models\ResourceAvailability;
use App\Models\ServiceStock;
use App\Models\Unit;
use Carbon\Carbon;
use Exception;

class DBRepo extends BaseDBRepo
{
    public function calculateQuote()
    {
        try {
            $quote = [
                'items' => [],
                'subtotal' => 0,
                'discounts' => [],
                'free_items' => [],
                'grand_total' => 0,
            ];
            $cartForPromoEngine = [];

            foreach ($this->payload['items'] as $item) {
                $processedItem = $this->processItem($item);
                $quote['items'][] = $processedItem;
                $quote['subtotal'] += $processedItem['subtotal'];
            }

            // ... Logika promo dan grand total ...

            return (object)['status' => true, 'data' => $quote];
        } catch (Exception $e) {
            return (object)['status' => false, 'message' => $e->getMessage()];
        }
    }


    private function processItem(array $item)
    {
        switch ($item['type']) {
            case 'BOOKING_TIMESLOT':
                return $this->processTimeSlotBooking($item);
            case 'BOOKING_DYNAMIC':
                return $this->processDynamicBooking($item);
            case 'GOODS':
                return $this->processGoods($item);
            case 'SERVICE':
                return $this->processService($item);
            default:
                throw new Exception("Unknown item type: {$item['type']}");
        }
    }

    private function processTimeSlotBooking(array $item)
    {
        $resource = Resource::with('product')->findOrFail($item['resource_id']);
        if (!in_array($resource->product->booking_mechanism, ['TIME_SLOT', 'TIME_SLOT_CAPACITY'])) {
            throw new Exception("Product is not a time-slot based service.");
        }

        $unit = Unit::findOrFail($item['unit_id']);
        $durationMinutes = ($unit->name === 'Hari' ? 1440 : 60) * $item['quantity'];

        $start = Carbon::parse($item['start_datetime']);
        $end = $start->copy()->addMinutes($durationMinutes);

        if (!$this->isWithinOperatingHours($resource->resource_id, $start, $end)) throw new Exception("Resource is not operational at the requested time.");
        if ($this->hasConflict($resource->resource_id, $start, $end)) throw new Exception("Resource is already booked at the requested time.");

        $price = $this->getPrice($resource->product->product_id, $item['unit_id']);

        // -- KUNCI PERBAIKAN --
        // Membuat 'name' yang lebih deskriptif untuk ditampilkan di quote.
        // Contoh: "Sewa Lapangan Badminton - Lapangan A (2 Jam)"
        $descriptiveName = sprintf(
            '%s - %s (%d %s)',
            $resource->product->name,
            $resource->name,
            $item['quantity'],
            $unit->name
        );
        // ------------------------

        return [
            'type' => 'BOOKING_TIMESLOT',
            'product_id' => $resource->product->product_id,
            'resource_id' => $resource->resource_id,
            'unit_id' => $unit->unit_id,
            'start_datetime' => $item['start_datetime'],
            'end_datetime' => $end->format('Y-m-d H:i:s'),
            'name' => $descriptiveName, // Menggunakan nama yang sudah diperbaiki
            'quantity' => $item['quantity'],
            'unit_price' => $price,
            'subtotal' => $item['quantity'] * $price
        ];
    }

    private function processDynamicBooking(array $item)
    {
        $resource = Resource::with('product')->findOrFail($item['resource_id']);
        if ($resource->activeSession()->where('status', 'ACTIVE')->exists()) {
            throw new Exception("Resource {$resource->name} is already in an active session.");
        }

        $unitJam = Unit::where('name', 'Jam')->firstOrFail();
        $price = $this->getPrice($resource->product->product_id, $unitJam->unit_id);

        return [
            'type' => 'BOOKING_DYNAMIC',
            'product_id' => $resource->product->product_id,
            'resource_id' => $resource->resource_id,
            'name' => $resource->product->name . ' - ' . $resource->name . ' (Sesi Dimulai)',
            'quantity' => 1,
            'unit_price' => $price, // Harga per jam
            'subtotal' => 0
        ];
    }

    private function processGoods(array $item)
    {
        $variant = ProductVariant::with('product')->findOrFail($item['variant_id']);
        if ($variant->stock_quantity < $item['quantity']) {
            throw new Exception("Insufficient stock for product: {$variant->name}");
        }
        $price = $this->getPrice($variant->product_id, null, $variant->variant_id);

        return [
            'type' => 'GOODS',
            'product_id' => $variant->product_id,
            'variant_id' => $variant->variant_id,
            'name' => $variant->product->name . ' - ' . $variant->name,
            'quantity' => $item['quantity'],
            'unit_price' => $price,
            'subtotal' => $item['quantity'] * $price
        ];
    }

    private function processService(array $item)
    {
        // Eager load relasi unit untuk mendapatkan namanya
        $stock = ServiceStock::with(['product', 'unit'])->findOrFail($item['stock_id']);
        if ($stock->available_quantity < $item['quantity']) {
            throw new Exception("Insufficient stock for service: {$stock->name}");
        }
        $price = $this->getPrice($stock->product_id, $stock->unit_id);

        // -- PERBAIKAN KONSISTENSI --
        // Membuat 'name' yang deskriptif, mirip dengan booking
        // Contoh: "Main Golf 9 Holes - Weekday Session (1 9 Holes)"
        $descriptiveName = sprintf(
            '%s - %s (%d %s)',
            $stock->product->name,
            $stock->name,
            $item['quantity'],
            $stock->unit->name // Mengambil nama dari relasi unit
        );
        // -------------------------

        return [
            'type' => 'SERVICE',
            'product_id' => $stock->product_id,
            'stock_id' => $stock->stock_id,
            'name' => $descriptiveName, // Menggunakan nama yang sudah diperbaiki
            'quantity' => $item['quantity'],
            'unit_price' => $price,
            'subtotal' => $item['quantity'] * $price
        ];
    }


    private function getPrice($productId, $unitId = null, $variantId = null)
    {
        $priceType = isset($this->payload['member_id']) ? 'MEMBER' : 'REGULAR';
        $query = Pricing::where('product_id', $productId)
            ->where('price_type', $priceType)
            ->when($unitId, fn($q) => $q->where('unit_id', $unitId))
            ->when($variantId, fn($q) => $q->where('variant_id', $variantId));

        $pricing = $query->first();

        if (!$pricing) {
            $queryRegular = Pricing::where('product_id', $productId)
                ->where('price_type', 'REGULAR')
                ->when($unitId, fn($q) => $q->where('unit_id', $unitId))
                ->when($variantId, fn($q) => $q->where('variant_id', $variantId));
            $pricing = $queryRegular->firstOrFail();
        }
        return $pricing->price;
    }

    private function isWithinOperatingHours($resourceId, Carbon $start, Carbon $end): bool
    {
        $dayOfWeek = $start->dayOfWeek;
        $schedule = ResourceAvailability::where('resource_id', $resourceId)->where('day_of_week', $dayOfWeek)->first();
        if (!$schedule) return false;
        return ($start->format('H:i:s') >= $schedule->start_time && $end->format('H:i:s') <= $schedule->end_time);
    }

    private function hasConflict($resourceId, Carbon $start, Carbon $end): bool
    {
        return Booking::where('resource_id', $resourceId)
            ->where('status', '!=', 'CANCELLED')
            ->where('start_datetime', '<', $end)
            ->where('end_datetime', '>', $start)
            ->exists();
    }
}
