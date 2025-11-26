<?php

namespace App\Http\Controllers\REST\V1\Pos\Carts\Items;

use App\Http\Libraries\BaseDBRepo;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\Package;
use App\Models\PackagePricing;
use App\Models\Pricing;
use App\Models\ProductVariant;
use App\Models\Rental;
use App\Models\Resource;
use App\Models\ResourceAvailability;
use App\Models\Unit;
use Exception;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
// Import DBRepo utama untuk memanggil recalculate
use App\Http\Controllers\REST\V1\Pos\Carts\DBRepo as CartsDBRepo;

class DBRepo extends BaseDBRepo
{
    private $cartsDBRepo;

    public function __construct(?array $payload = [], ?array $file = [], ?array $auth = [])
    {
        parent::__construct($payload, $file, $auth);
        // Buat instance dari DBRepo utama untuk mengakses methodnya
        $this->cartsDBRepo = new CartsDBRepo($payload, $file, $auth);
    }

    /*
     * =================================================================================
     * METHOD PUBLIK (Dipanggil oleh Controller Payload di namespace Carts\Items)
     * =================================================================================
     */

    public function addItem(int $cartId)
    {
        try {
            return DB::transaction(function () use ($cartId) {
                $cart = Cart::with('customer')->where('id', $cartId)->lockForUpdate()->firstOrFail();

                $itemData = $this->calculateAndValidateItem($this->payload, $cart->customer);

                $cart->items()->create($itemData);
                $this->cartsDBRepo->recalculateCartTotal($cart);

                return (object)['status' => true, 'data' => $cart->load('items')->toArray()];
            });
        } catch (Exception $e) {
            return (object)['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateItem(int $cartId, int $itemId)
    {
        try {
            return DB::transaction(function () use ($cartId, $itemId) {
                $cart = Cart::with('customer')->where('id', $cartId)->lockForUpdate()->firstOrFail();
                $item = $cart->items()->findOrFail($itemId);

                // Buat payload baru dari data item yang ada, lalu timpa dengan kuantitas baru
                $newItemPayload = $item->toArray();
                $newItemPayload['quantity'] = $this->payload['quantity'];

                // Kalkulasi ulang item dengan kuantitas baru
                $recalculatedItemData = $this->calculateAndValidateItem($newItemPayload, $cart->customer);

                $item->update($recalculatedItemData);

                $this->cartsDBRepo->recalculateCartTotal($cart);
                return (object)['status' => true, 'data' => $cart->load('items')->toArray()];
            });
        } catch (Exception $e) {
            return (object)['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteItem(int $cartId, int $itemId)
    {
        try {
            return DB::transaction(function () use ($cartId, $itemId) {
                $cart = Cart::where('id', $cartId)->lockForUpdate()->firstOrFail();
                $cart->items()->findOrFail($itemId)->delete();
                $this->cartsDBRepo->recalculateCartTotal($cart);
                return (object)['status' => true, 'data' => $cart->load('items')->toArray()];
            });
        } catch (Exception $e) {
            return (object)['status' => false, 'message' => $e->getMessage()];
        }
    }

    /*
     * =================================================================================
     * MESIN KALKULASI INTERNAL
     * =================================================================================
     */

    private function calculateAndValidateItem(array $itemPayload, ?Customer $customer): array
    {
        $customerCategoryId = $customer->customer_category_id ?? null;

        switch ($itemPayload['type']) {
            case 'CONSUMPTION':
                $variant = ProductVariant::with('product')->findOrFail($itemPayload['variant_id']);
                if ($variant->stock_quantity !== null && $variant->stock_quantity < $itemPayload['quantity']) {
                    throw new Exception("Insufficient stock for: {$variant->name}");
                }
                $pricing = $this->getPricingRule($customerCategoryId, null, $variant->variant_id, null);
                return [
                    'type' => 'CONSUMPTION',
                    'variant_id' => $variant->variant_id,
                    'name' => $variant->product->name . ' - ' . $variant->name,
                    'quantity' => $itemPayload['quantity'],
                    'unit_price' => $pricing->price,
                    'subtotal' => $itemPayload['quantity'] * $pricing->price
                ];

            case 'RENTAL_FIXED':
                $resource = Resource::with('product')->findOrFail($itemPayload['resource_id']);
                $pricing = $this->getPricingRule($customerCategoryId, null, null, $resource->resource_id);
                $unit = $pricing->unit;
                $start = Carbon::parse($itemPayload['start_datetime']);
                $durationSeconds = $unit->value_in_seconds * $itemPayload['quantity'];
                $end = $start->copy()->addSeconds($durationSeconds);

                if (!$this->isWithinOperatingHours($resource->resource_id, $start, $end)) throw new Exception("Resource not operational.");
                if ($this->hasConflict($resource->resource_id, $start, $end)) throw new Exception("Resource already booked.");

                $descriptiveName = sprintf('%s - %s (%d %s)', $resource->product->name, $resource->name, $itemPayload['quantity'], $unit->name);
                return [
                    'type' => 'RENTAL_FIXED',
                    'resource_id' => $resource->resource_id,
                    'unit_id' => $unit->unit_id,
                    'start_datetime' => $itemPayload['start_datetime'],
                    'end_datetime' => $end->format('Y-m-d H:i:s'),
                    'name' => $descriptiveName,
                    'quantity' => $itemPayload['quantity'],
                    'unit_price' => $pricing->price,
                    'subtotal' => $itemPayload['quantity'] * $pricing->price
                ];

            case 'PACKAGE':
                $package = Package::with('items.variant')->findOrFail($itemPayload['package_id']);

                // Validasi Stok untuk setiap item di dalam paket
                foreach ($package->items as $packageItem) {
                    if ($packageItem->item_type === 'VARIANT') {
                        $requiredStock = $packageItem->quantity * $itemPayload['quantity'];
                        if ($packageItem->variant->stock_quantity !== null && $packageItem->variant->stock_quantity < $requiredStock) {
                            throw new Exception("Insufficient stock for item '{$packageItem->variant->name}' in package '{$package->name}'.");
                        }
                    }
                }

                // Algoritma #4: Dapatkan harga dari package_pricings
                $packagePricing = $this->getPackagePricingRule($customerCategoryId, $package->id);

                return [
                    'type' => 'PACKAGE',
                    'package_id' => $package->id,
                    'name' => $package->name,
                    'quantity' => $itemPayload['quantity'],
                    'unit_price' => $packagePricing->price,
                    'subtotal' => $itemPayload['quantity'] * $packagePricing->price
                ];

            case 'RENTAL_DYNAMIC':
                $resource = Resource::with('product')->findOrFail($itemPayload['resource_id']);
                $now = now();

                // Validasi ketersediaan
                if ($this->hasConflict($resource->resource_id, $now, $now->copy()->addSecond())) {
                    throw new Exception("Resource '{$resource->name}' is currently unavailable.");
                }
                if (!$this->isWithinOperatingHours($resource->resource_id, $now, $now->copy()->addSecond())) {
                    throw new Exception("Resource '{$resource->name}' is not operational at this time.");
                }

                // Ambil aturan harga PERTAMA yang ditemukan untuk resource ini.
                // Ini mengasumsikan satu resource hanya punya satu cara jual dinamis.
                $pricing = $this->getPricingRule($customer->customer_category_id ?? null, null, null, $resource->resource_id);

                return [
                    'type' => 'RENTAL_DYNAMIC',
                    'resource_id' => $resource->resource_id,
                    'unit_id' => $pricing->unit_id, // Simpan unit dari aturan harga
                    'start_datetime' => $now->toDateTimeString(),
                    'end_datetime' => null,
                    'name' => $resource->product->name . ' - ' . $resource->name . ' (Sedang Berjalan)',
                    'quantity' => 1,
                    'unit_price' => $pricing->price,
                    'subtotal' => 0,
                ];
        }
        throw new Exception("Unsupported item type: {$itemPayload['type']}");
    }

    private function getPricingRule(?int $customerCategoryId, ?int $unitId = null, ?int $variantId = null, ?int $resourceId = null): Pricing
    {
        // 1. Coba cari harga spesifik untuk kategori customer ini
        $query = Pricing::query()->where('customer_category_id', $customerCategoryId);
        if ($variantId) $query->where('variant_id', $variantId);
        elseif ($resourceId) $query->where('resource_id', $resourceId);
        $pricing = $query->first();

        // 2. Fallback: Jika tidak ada, cari harga Umum (di mana customer_category_id adalah NULL)
        if (!$pricing && $customerCategoryId !== null) {
            $queryUmum = Pricing::query()->whereNull('customer_category_id');
            if ($variantId) $queryUmum->where('variant_id', $variantId);
            elseif ($resourceId) $queryUmum->where('resource_id', $resourceId);
            $pricing = $queryUmum->first();
        }
        if (!$pricing) throw new Exception("No pricing rule found for the specified item.");
        return $pricing;
    }

    /**
     * Mengambil ATURAN HARGA (objek PackagePricing) untuk PAKET.
     * @param int|null $customerCategoryId
     * @param int $packageId
     * @return PackagePricing
     * @throws Exception
     */
    private function getPackagePricingRule(?int $customerCategoryId, int $packageId): PackagePricing
    {
        // 1. Coba cari harga spesifik untuk kategori customer ini
        $packagePricing = PackagePricing::where('package_id', $packageId)
            ->where('customer_category_id', $customerCategoryId)
            ->first();

        // 2. Fallback: Jika tidak ada, cari harga Umum (di mana customer_category_id adalah NULL)
        if (!$packagePricing && $customerCategoryId !== null) {
            $packagePricing = PackagePricing::where('package_id', $packageId)
                ->whereNull('customer_category_id')
                ->first();
        }

        if (!$packagePricing) {
            throw new Exception("No pricing rule found for the specified package.");
        }
        return $packagePricing;
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
        return Rental::where('resource_id', $resourceId)
            ->where('status', '!=', 'CANCELLED')
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();
    }

    public function endSessionInCart(int $cartId, int $cartItemId)
    {
        try {
            return DB::transaction(function () use ($cartId, $cartItemId) {
                $cart = Cart::with('customer')->where('id', $cartId)->lockForUpdate()->firstOrFail();
                $item = $cart->items()->findOrFail($cartItemId);

                if ($item->type !== 'RENTAL_DYNAMIC' || $item->end_datetime !== null) {
                    throw new Exception('This item is not an active dynamic session.');
                }

                // Algoritma #3c: Hitung durasi dan harga
                $endTime = now();
                $startTime = Carbon::parse($item->start_datetime);
                $totalSeconds = $startTime->diffInSeconds($endTime);

                $pricing = $this->getPricingRule($cart->customer_id, null, null, $item->resource_id);
                $unit = $pricing->unit;

                if ($unit->value_in_seconds <= 0) throw new Exception('Invalid unit value for calculation.');

                $billedUnits = ceil($totalSeconds / $unit->value_in_seconds);
                if ($billedUnits < 1) $billedUnits = 1; // Tagih minimal 1 unit

                $subtotal = $billedUnits * $pricing->price;

                // Update item di keranjang
                $item->update([
                    'end_datetime' => $endTime,
                    'quantity' => $billedUnits,
                    'subtotal' => $subtotal,
                    'name' => str_replace('(Sedang Berjalan)', '', $item->name) . ' (Selesai)',
                ]);

                $this->cartsDBRepo->recalculateCartTotal($cart);
                return (object)['status' => true, 'data' => $cart->load('items')->toArray()];
            });
        } catch (Exception $e) {
            return (object)['status' => false, 'message' => $e->getMessage()];
        }
    }
}
