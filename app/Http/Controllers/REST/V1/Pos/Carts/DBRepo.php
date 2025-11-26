<?php

namespace App\Http\Controllers\REST\V1\Pos\Carts;

use App\Http\Libraries\BaseDBRepo;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\Tax;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DBRepo extends BaseDBRepo
{
    /*
     * =================================================================================
     * METHOD PUBLIK (Dipanggil oleh Controller Payload di namespace Carts)
     * =================================================================================
     */

    /**
     * Membuat sesi keranjang baru.
     * @return object
     */
    public function createCart()
    {
        try {
            return DB::transaction(function () {
                $cart = Cart::create([
                    'outlet_id' => $this->auth['outlet_id'],
                    'employee_id' => $this->auth['employee_id'],
                ]);
                return (object)['status' => true, 'data' => $cart->toArray()];
            });
        } catch (Exception $e) {
            return (object)['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Mengambil detail lengkap dari satu keranjang.
     * @param int $cartId
     * @return object
     */
    public function getCart(int $cartId)
    {
        try {
            $businessId = $this->auth['business_id'];

            $cart = Cart::with(['items.variant', 'customer'])->findOrFail($cartId);

            $taxDetails = $this->calculateTaxes($businessId, $cart->grand_total);
            $cart->total_tax = $taxDetails['total_tax'];
            $cart->final_total = $taxDetails['final_total'];
            $cart->applied_taxes = $taxDetails['applied_taxes'];

            foreach ($cart->items as $item) {
                $item->product_id = $item->variant->product_id;
            }

            return (object)['status' => true, 'data' => $cart->toArray()];
        } catch (Exception $e) {
            return (object)['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Memperbarui data keranjang, seperti meng-assign customer.
     * @param int $cartId
     * @return object
     */
    public function updateCart(int $cartId)
    {
        try {
            return DB::transaction(function () use ($cartId) {
                $cart = Cart::where('id', $cartId)->lockForUpdate()->firstOrFail();
                $oldCustomerId = $cart->customer_id;

                $updatePayload = Arr::only($this->payload, ['customer_id']);

                $cart->update($updatePayload);

                $newCustomerId = $cart->customer_id;
                if ($oldCustomerId !== $newCustomerId) {
                    $this->recalculateCartTotal($cart);
                }

                return (object)['status' => true];
            });
        } catch (Exception $e) {
            return (object)['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Membatalkan (soft delete) sebuah keranjang.
     * @param int $cartId
     * @return object
     */
    public function cancelCart(int $cartId)
    {
        try {
            return DB::transaction(function () use ($cartId) {
                $cart = Cart::findOrFail($cartId);
                $cart->update(['status' => 'CANCELLED']);
                return (object)['status' => true];
            });
        } catch (Exception $e) {
            return (object)['status' => false, 'message' => $e->getMessage()];
        }
    }

    /*
     * =================================================================================
     * METHOD PUBLIK (Untuk Digunakan oleh DBRepo Lain)
     * =================================================================================
     */

    /**
     * Mesin Kalkulasi Ulang Total Keranjang.
     * Method ini bersifat publik agar bisa dipanggil oleh Carts\Items\DBRepo.
     * @param Cart $cart
     * @return void
     * @throws Exception
     */
    public function recalculateCartTotal(Cart &$cart)
    {
        // Muat ulang relasi items untuk memastikan data konsisten
        $cart->load('items');
        if ($cart->items->isEmpty()) {
            $cart->update(['subtotal' => 0, 'total_discount' => 0, 'grand_total' => 0]);
            return;
        }

        // Cukup jumlahkan subtotal yang sudah dihitung per item
        $currentSubtotal = $cart->items->sum('subtotal');

        // Di sini kita bisa terapkan promo engine pada seluruh keranjang
        // Untuk saat ini, kita gunakan placeholder
        $promoResult = ['total_discount' => 0, 'free_items' => []];

        // Update total di tabel cart utama
        $cart->update([
            'subtotal' => $currentSubtotal,
            'total_discount' => $promoResult['total_discount'],
            'grand_total' => $currentSubtotal - $promoResult['total_discount'],
        ]);
    }

    public function getActiveCartsSummary()
    {
        try {
            $outletId = $this->auth['outlet_id'];
            $businessId = $this->auth['business_id'];
            $perPage = $this->payload['per_page'] ?? 15;

            $query = Cart::query()
                ->with([
                    'customer' => fn($q) => $q->select('id', 'name'),
                    'employee' => fn($q) => $q->select('id', 'name')
                ])
                ->withCount('items')
                ->where('outlet_id', $outletId)
                ->where('status', 'ACTIVE')
                ->orderBy('id', 'desc');

            // print_r($query);

            $paginatedResult = $query->paginate($perPage);

            // Ambil semua pajak aktif untuk outlet ini sekali saja untuk efisiensi
            $activeTaxes = Tax::where('business_id', $businessId)->where('is_active', true)->get();

            $paginatedResult->getCollection()->transform(function ($cart) use ($activeTaxes) {
                // --- PERUBAHAN DI SINI ---
                // Hitung pajak untuk setiap keranjang di dalam summary
                $taxDetails = $this->calculateTaxesWithPreloadedTaxes($activeTaxes, $cart->grand_total);
                // ------------------------

                return [
                    'id' => $cart->id,
                    'customer_name' => $cart->customer->name ?? 'Walk-in Customer',
                    'employee_name' => $cart->employee->name ?? 'Unknown',
                    'items_count' => $cart->items_count,
                    'grand_total' => $cart->grand_total,
                    'total_tax' => $taxDetails['total_tax'],     // Tambahkan total pajak
                    'final_total' => $taxDetails['final_total'], // Tambahkan total akhir setelah pajak
                    'created_at' => $cart->created_at->toDateTimeString(),
                ];
            });

            return (object)['status' => true, 'data' => $paginatedResult->toArray()];
        } catch (Exception $e) {
            return (object)['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Menghitung total pajak berdasarkan outlet dan subtotal.
     * @param int $businessId
     * @param float $subtotal (grand_total sebelum pajak)
     * @return array
     */
    private function calculateTaxes(int $businessId, float $subtotal): array
    {
        // Ambil semua pajak yang AKTIF untuk outlet ini
        $activeTaxes = Tax::where('business_id', $businessId)
            ->where('is_active', true)
            ->get();

        return $this->calculateTaxesWithPreloadedTaxes($activeTaxes, $subtotal);
    }

    /**
     * Logika inti perhitungan pajak, menerima pajak yang sudah di-load.
     * @param \Illuminate\Database\Eloquent\Collection $taxes
     * @param float $subtotal
     * @return array
     */
    private function calculateTaxesWithPreloadedTaxes($taxes, float $subtotal): array
    {
        $totalTax = 0;
        $appliedTaxes = [];

        foreach ($taxes as $tax) {
            $taxAmount = 0;
            if ($tax->type === 'PERCENTAGE') {
                $taxAmount = ($subtotal * $tax->rate) / 100;
            } elseif ($tax->type === 'FIXED') {
                $taxAmount = $tax->rate;
            }

            $totalTax += $taxAmount;
            $appliedTaxes[] = [
                'name' => $tax->name,
                'rate' => (float) $tax->rate,
                'type' => $tax->type,
                'amount' => round($taxAmount, 2)
            ];
        }

        return [
            'total_tax' => round($totalTax, 2),
            'final_total' => round($subtotal + $totalTax, 2),
            'applied_taxes' => $appliedTaxes,
        ];
    }
}
