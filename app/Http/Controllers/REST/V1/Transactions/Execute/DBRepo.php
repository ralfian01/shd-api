<?php

namespace App\Http\Controllers\REST\V1\Transactions\Execute;

use App\Http\Libraries\BaseDBRepo;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\Booking;
use App\Models\ActiveSession;
use App\Models\ProductVariant;
use App\Models\ServiceStock;
use App\Models\TransactionItem; // Import TransactionItem
use Exception;
use Illuminate\Support\Facades\DB;

class DBRepo extends BaseDBRepo
{
    /**
     * Fungsi utama untuk mengeksekusi transaksi berdasarkan quote yang sudah divalidasi.
     * @return object
     */
    public function executeTransaction()
    {
        try {
            return DB::transaction(function () {
                $quote = $this->payload['quote'];
                $customerData = $this->payload['customer'];
                $paymentData = $this->payload['payment'];
                $memberId = $this->payload['member_id'] ?? null;

                // 1. Buat atau Update Customer
                $customer = Customer::updateOrCreate(
                    ['phone_number' => $customerData['phone_number']],
                    $customerData
                );

                // 2. Buat Transaksi Utama
                $transaction = Transaction::create([
                    'customer_id' => $customer->id,
                    'member_id' => $memberId,
                    'total_amount' => $quote['grand_total'],
                    'payment_method_id' => $paymentData['payment_method_id'],
                    'cash_received' => $paymentData['cash_received'] ?? null,
                    'change_due' => ($paymentData['cash_received'] ?? $quote['grand_total']) - $quote['grand_total'],
                ]);

                // 3. Proses setiap item dalam quote untuk membuat record dan mengurangi stok
                foreach ($quote['items'] as $item) {
                    // Buat TransactionItem dengan semua jejak (traceability) yang diperlukan
                    $trxItem = $transaction->items()->create([
                        'name' => $item['name'], // -- DITAMBAHKAN --
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'] ?? null,
                        'resource_id' => $item['resource_id'] ?? null,
                        'service_stock_id' => $item['stock_id'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $item['subtotal'],
                    ]);

                    // Lakukan tindakan spesifik berdasarkan tipe item
                    switch ($item['type']) {
                        case 'BOOKING_TIMESLOT':
                            $booking = Booking::create([
                                'resource_id' => $item['resource_id'],
                                'start_datetime' => $item['start_datetime'],
                                'end_datetime' => $item['end_datetime'],
                                'status' => 'CONFIRMED'
                            ]);
                            // Hubungkan item transaksi ke booking yang baru dibuat
                            $trxItem->update(['booking_id' => $booking->id]);
                            break;

                        case 'BOOKING_DYNAMIC':
                            $session = ActiveSession::create([
                                'resource_id' => $item['resource_id'],
                                'start_time' => now(),
                                'status' => 'ACTIVE'
                            ]);
                            // Hubungkan item transaksi ke sesi yang baru dibuat
                            $trxItem->update(['session_id' => $session->id]);
                            break;

                        case 'GOODS':
                            // Kurangi stok barang
                            ProductVariant::where('variant_id', $item['variant_id'])->decrement('stock_quantity', $item['quantity']);
                            break;

                        case 'SERVICE':
                            // Kurangi stok layanan
                            ServiceStock::where('stock_id', $item['stock_id'])->decrement('available_quantity', $item['quantity']);
                            break;
                    }
                }

                // Tambahkan logika untuk menyimpan item diskon/gratis dari quote jika ada

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
}
