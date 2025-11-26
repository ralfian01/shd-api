<?php

namespace App\Http\Controllers\REST\V1\Pos\PaymentMethods;

use App\Http\Libraries\BaseDBRepo;
use App\Models\PaymentMethod;
use Exception;
use Illuminate\Support\Facades\DB;

class DBRepo extends BaseDBRepo
{
    /*
     * =================================================================================
     * METHOD UNTUK MENGAMBIL DATA (GET)
     * =================================================================================
     */

    /**
     * Fungsi utama untuk mengambil data metode pembayaran berdasarkan filter.
     * @return object
     */
    public function getData()
    {
        try {
            $query = PaymentMethod::query()
                ->where('is_active', true);

            // Kasus 2: Mengambil daftar data dengan filter dan paginasi
            $this->applyFilters($query);

            $perPage = $this->payload['per_page'] ?? 20;
            $data = $query->paginate($perPage);

            return (object) [
                'status' => true,
                'data' => $data->toArray(),
            ];
        } catch (Exception $e) {
            return (object) [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Method pendukung untuk menerapkan filter pada query GET.
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    private function applyFilters(&$query)
    {
        if (isset($this->payload['type'])) {
            $query->where('type', $this->payload['type']);
        }
    }
}
