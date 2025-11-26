<?php

namespace App\Http\Controllers\REST\V1\Pos\Customers;

use App\Http\Libraries\BaseDBRepo;
use App\Models\Customer;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DBRepo extends BaseDBRepo
{
    /*
     * =================================================================================
     * METHOD UNTUK MENGAMBIL DATA (GET)
     * =================================================================================
     */
    public function getData()
    {
        try {
            $businessId = $this->auth['business_id'];

            $query = Customer::query()
                ->where('business_id', $businessId);

            if (isset($this->payload['keyword'])) {
                $keyword = $this->payload['keyword'];
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery->where('name', 'LIKE', "%{$keyword}%")
                        ->orWhere('phone_number', 'LIKE', "%{$keyword}%")
                        ->orWhere('email', 'LIKE', "%{$keyword}%");
                });
            }

            $perPage = $this->payload['per_page'] ?? 15;
            $data = $query->paginate($perPage);

            return (object) ['status' => true, 'data' => $data->toArray()];
        } catch (Exception $e) {
            return (object) ['status' => false, 'message' => $e->getMessage()];
        }
    }
}
