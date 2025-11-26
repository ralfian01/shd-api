<?php

namespace App\Http\Controllers\REST\V1\Transactions\Booking;

use App\Http\Libraries\BaseDBRepo;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Member;
use App\Models\PaymentMethod;
use App\Models\Pricing;
use App\Models\Resource;
use App\Models\Transaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class DBRepo extends BaseDBRepo
{
    private $bookingItem;
    private $unit;
    private $resource;
    private $product;
    private $pricing;

    private function loadEntities()
    {
        $this->bookingItem = $this->payload['booking_item'];
        $this->resource = Resource::with('product')->findOrFail($this->bookingItem['resource_id']);
        $this->product = $this->resource->product;

        // -- LOGIKA HARGA BARU --
        // Cek apakah ada member_id dan gunakan harga MEMBER jika ada
        $priceType = isset($this->payload['member_id']) ? 'MEMBER' : 'REGULAR';

        $this->pricing = Pricing::where('product_id', $this->product->product_id)
            ->where('unit_id', $this->bookingItem['unit_id'])
            ->where('price_type', $priceType)
            ->first();

        // Fallback ke harga REGULAR jika harga MEMBER tidak ditemukan
        if (!$this->pricing) {
            $this->pricing = Pricing::where('product_id', $this->product->product_id)
                ->where('unit_id', $this->bookingItem['unit_id'])
                ->where('price_type', 'REGULAR')
                ->firstOrFail();
        }
    }


    public function checkAvailabilityForTransaction(): bool
    {
        $this->loadEntities(); // Pastikan data sudah dimuat
        $start = Carbon::parse($this->bookingItem['start_datetime']);
        // Asumsi sementara: unit berbasis durasi
        $durationMinutes = 60 * $this->bookingItem['quantity']; // Perlu logika lebih canggih jika unit bukan jam
        $end = $start->copy()->addMinutes($durationMinutes);

        return !Booking::where('resource_id', $this->resource->resource_id)
            ->where('status', '!=', 'CANCELLED')
            ->where('start_datetime', '<', $end)
            ->where('end_datetime', '>', $start)
            ->exists();
    }

    public function validatePayment()
    {
        $this->loadEntities(); // Pastikan data sudah dimuat
        $totalAmount = $this->bookingItem['quantity'] * $this->pricing->price;
        $paymentMethod = PaymentMethod::find($this->payload['payment']['payment_method_id']);

        if ($paymentMethod->type === 'CASH') {
            if (empty($this->payload['payment']['cash_received'])) {
                return (object)['status' => false, 'message' => 'Cash amount is required for cash payment.'];
            }
            if ($this->payload['payment']['cash_received'] < $totalAmount) {
                return (object)['status' => false, 'message' => 'Insufficient cash received.'];
            }
        }
        return (object)['status' => true];
    }

    public function insertData()
    {
        try {
            return DB::transaction(function () {
                $this->loadEntities();

                // 1. Handle Customer (selalu ada)
                $customerData = $this->payload['customer'];
                $customer = Customer::updateOrCreate(
                    ['phone_number' => $customerData['phone_number']],
                    $customerData
                );

                // 2. Hitung Total & Kembalian
                $totalAmount = $this->bookingItem['quantity'] * $this->pricing->price;
                $cashReceived = $this->payload['payment']['cash_received'] ?? null;
                $changeDue = $cashReceived ? ($cashReceived - $totalAmount) : 0;

                // 3. Buat Transaksi Utama
                $transaction = Transaction::create([
                    'customer_id' => $customer->id,
                    'member_id' => $this->payload['member_id'] ?? null,
                    'total_amount' => $totalAmount,
                    'payment_method_id' => $this->payload['payment']['payment_method_id'],
                    'cash_received' => $cashReceived,
                    'change_due' => $changeDue,
                ]);

                // ... (langkah 4 & 5 untuk membuat Booking dan TransactionItem tetap sama) ...
                $start = Carbon::parse($this->bookingItem['start_datetime']);
                $durationMinutes = 60 * $this->bookingItem['quantity'];
                $end = $start->copy()->addMinutes($durationMinutes);

                $booking = Booking::create([
                    'resource_id' => $this->resource->resource_id,
                    'start_datetime' => $start,
                    'end_datetime' => $end,
                    'status' => 'CONFIRMED'
                ]);

                $transaction->items()->create([
                    'product_id' => $this->product->product_id,
                    'booking_id' => $booking->id,
                    'price_id' => $this->pricing->price_id,
                    'quantity' => $this->bookingItem['quantity'],
                    'unit_price' => $this->pricing->price,
                    'subtotal' => $totalAmount,
                ]);

                return (object) [
                    'status' => true,
                    'data' => [
                        'transaction_id' => $transaction->id,
                        'change_due' => $transaction->change_due
                    ]
                ];
            });
        } catch (Exception $e) {
            return (object) ['status' => false, 'message' => $e->getMessage()];
        }
    }
}
