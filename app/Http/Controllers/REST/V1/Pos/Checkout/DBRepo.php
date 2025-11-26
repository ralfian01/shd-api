<?php

namespace App\Http\Controllers\REST\V1\Pos\Checkout;

use App\Http\Libraries\BaseDBRepo;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\PosTransaction;
use App\Models\Rental;
use App\Models\ProductVariant;
use App\Models\Package;
use App\Models\Tax;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DBRepo extends BaseDBRepo
{
    /**
     * Validasi pra-checkout untuk memastikan keranjang siap diproses.
     */
    public static function validateCheckout(array $payload): object
    {
        $cart = Cart::find($payload['cart_id']);
        if (!$cart) {
            return (object)['status' => false, 'message' => 'Cart not found.'];
        }
        if ($cart->status !== 'ACTIVE') {
            return (object)['status' => false, 'message' => 'This cart is no longer active.'];
        }
        if ($cart->items()->count() === 0) {
            return (object)['status' => false, 'message' => 'Cannot checkout an empty cart.'];
        }
        return (object)['status' => true];
    }

    /**
     * Mengeksekusi proses checkout: mengubah keranjang menjadi transaksi permanen.
     * @return object
     */
    public function executeCheckout()
    {
        try {
            return DB::transaction(function () {
                // 1. Ambil Keranjang dengan semua relasi yang dibutuhkan untuk aksi
                $cart = Cart::with([
                    'items.variant.product',
                    'items.resource.product',
                    'items.package.items.variant' // Untuk mengurangi stok item paket
                ])->lockForUpdate()->findOrFail($this->payload['cart_id']);

                // 2. Buat atau Update Customer
                $customerId = null;

                // Skenario 1: Klien mengirim data customer baru
                if (isset($this->payload['customer'])) {
                    $customer = Customer::updateOrCreate(
                        ['phone_number' => $this->payload['customer']['phone_number']],
                        $this->payload['customer']
                    );
                    $customerId = $customer->id;

                    // Skenario 2: Klien tidak mengirim data customer baru,
                    //             tapi customer sudah ter-assign di keranjang sebelumnya.
                } elseif ($cart->customer_id) {
                    $customerId = $cart->customer_id;

                    // Skenario 3: Transaksi walk-in, tidak ada customer.
                } else {
                    $customerId = null;
                }


                // 3. Hitung Pajak dan Pembayaran Final
                $businessId = $this->auth['business_id'];

                $taxDetails = $this->calculateTaxes($businessId, $cart->grand_total);
                $finalTotalWithTax = $taxDetails['final_total'];
                $cashReceived = $this->payload['payment']['cash_received'] ?? null;
                $changeDue = $cashReceived ? ($cashReceived - $finalTotalWithTax) : 0;

                if ($changeDue < 0) {
                    throw new Exception('Insufficient cash received.');
                }

                // 4. Buat Transaksi Utama (Struk)
                $transaction = PosTransaction::create([
                    'cart_id' => $cart->id,
                    'customer_id' => $customerId,
                    'payment_method_id' => $this->payload['payment']['payment_method_id'],
                    'total_amount' => $finalTotalWithTax,
                    'cash_received' => $cashReceived,
                    'change_due' => $changeDue,
                ]);

                // 5. Salin Item dari Keranjang ke Item Transaksi & Lakukan Aksi
                foreach ($cart->items as $cartItem) {
                    $trxItem = $transaction->items()->create(
                        // Salin semua data relevan dari cart_item
                        Arr::except($cartItem->toArray(), ['id', 'cart_id', 'created_at', 'updated_at'])
                    );

                    // 6. Jalankan Aksi Bisnis (kurangi stok, buat booking, dll.)
                    switch ($cartItem->type) {
                        case 'CONSUMPTION':
                            ProductVariant::where('variant_id', $cartItem->variant_id)
                                ->decrement('stock_quantity', $cartItem->quantity);
                            break;

                        case 'RENTAL_FIXED':
                            $rental = Rental::create([
                                'product_id' => $cartItem->resource->product_id,
                                'resource_id' => $cartItem->resource_id,
                                'customer_id' => $customerId,
                                'rental_type' => 'FIXED_TIME',
                                'status' => 'CONFIRMED',
                                'start_time' => $cartItem->start_datetime,
                                'end_time' => $cartItem->end_datetime,
                                'transaction_item_id' => $trxItem->id
                            ]);
                            $trxItem->update(['rental_id' => $rental->rental_id]);
                            break;

                        case 'RENTAL_DYNAMIC':
                            // Untuk sesi dinamis, record rental sudah dibuat.
                            // Kita hanya perlu menghubungkannya ke item transaksi ini.
                            Rental::where('rental_id', $cartItem->rental_id)
                                ->update(['transaction_item_id' => $trxItem->id]);
                            break;

                        case 'PACKAGE':
                            // Kurangi stok untuk setiap item VARIAN di dalam paket
                            foreach ($cartItem->package->items as $packageItem) {
                                if ($packageItem->item_type === 'VARIANT') {
                                    $requiredStock = $packageItem->quantity * $cartItem->quantity;
                                    ProductVariant::where('variant_id', $packageItem->item_id)
                                        ->decrement('stock_quantity', $requiredStock);
                                }
                            }
                            break;
                    }
                }

                // 7. Update status Cart menjadi COMPLETED
                $cart->update(['status' => 'COMPLETED']);

                return (object)[
                    'status' => true,
                    'data' => [
                        'transaction_id' => $transaction->id,
                        'change_due' => $transaction->change_due
                    ]
                ];
            });
        } catch (Exception $e) {
            return (object)['status' => false, 'message' => $e->getMessage()];
        }
    }

    // Method ini bisa di-copy dari Carts\DBRepo atau diletakkan di Trait/Helper
    private function calculateTaxes(int $businessId, float $subtotal): array
    {
        $activeTaxes = Tax::where('business_id', $businessId)->where('is_active', true)->get();
        $totalTax = 0;
        foreach ($activeTaxes as $tax) {
            if ($tax->type === 'PERCENTAGE') {
                $totalTax += ($subtotal * $tax->rate) / 100;
            } else {
                $totalTax += $tax->rate;
            }
        }
        return [
            'total_tax' => round($totalTax, 2),
            'final_total' => round($subtotal + $totalTax, 2),
        ];
    }
}
