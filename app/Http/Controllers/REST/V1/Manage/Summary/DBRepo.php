<?php

namespace App\Http\Controllers\REST\V1\Manage\Summary;

use App\Http\Libraries\BaseDBRepo;
use App\Models\Sale;
use App\Models\Variant;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class DBRepo extends BaseDBRepo
{
    public function __construct(?array $payload = [], ?array $file = [], ?array $auth = [])
    {
        parent::__construct($payload, $file, $auth);
    }

    /*
    |--------------------------------------------------------------------------
    | DATABASE TRANSACTION
    |--------------------------------------------------------------------------
    */

    /**
     * Mengambil dan menggabungkan beberapa data agregat untuk summary dashboard.
     *
     * @return object
     */
    public function getSummaryData()
    {
        try {
            // 1. Menghitung Total Pendapatan
            // Menggunakan DB::raw untuk mengalikan kuantitas dengan harga satuan
            $totalRevenue = Sale::sum(DB::raw('quantity * unit_price'));

            // 2. Menghitung Total Produk Terjual Bulan Ini
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();
            $monthlySalesCount = Sale::whereBetween('purchase_date', [$startOfMonth, $endOfMonth])->count();

            // 3. Menghitung Total SKU dari Variant
            $totalSkuCount = Variant::count();

            // 4. Mengambil 5 Penjualan Terbaru
            $recentSales = Sale::query()
                ->latest('purchase_date')
                ->limit(5)
                ->get();

            // Menggabungkan semua data menjadi satu array response
            $summaryData = [
                'total_revenue' => (float) $totalRevenue,
                'monthly_sales_count' => $monthlySalesCount,
                'total_sku_count' => $totalSkuCount,
                'recent_sales' => $recentSales->toArray(),
            ];

            return (object) [
                'status' => true,
                'data' => $summaryData,
            ];
        } catch (Exception $e) {
            return (object) [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
