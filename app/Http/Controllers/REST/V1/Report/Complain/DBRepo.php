<?php

namespace App\Http\Controllers\REST\V1\Report\Complain;

use App\Http\Controllers\REST\V1\Manage\Schedule\Get as GetSchedule;
use App\Http\Libraries\BaseDBRepo;
use App\Models\ComplainModel;
use App\Models\EmployeeModel;
use App\Models\MachineModel;
use App\Models\ProductModel;
use App\Models\ScheduleEmployeeModel;
use App\Models\ScheduleModel;
use DateTime;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * 
 */
class DBRepo extends BaseDBRepo
{
    // public function __construct(?array $payload = [], ?array $file = [], ?array $auth = [])
    // {
    //     parent::__construct($payload, $file, $auth);
    // }

    /*
     * ---------------------------------------------
     * TOOLS
     * ---------------------------------------------
     */

    /**
     * Function to check schedule id
     * @return bool
     */
    public static function checkComplainId($id)
    {
        return ComplainModel::find($id) != null;
    }

    /**
     * Function to check product id
     * @return bool
     */
    public static function checkProductId($id)
    {
        return ProductModel::find($id) != null;
    }


    /*
     * ---------------------------------------------
     * DATABASE TRANSACTION
     * ---------------------------------------------
     */

    /**
     * Function to get data from database
     * @return array|null|object
     */
    public function getData()
    {
        ## Formatting additional data which not payload
        // Code here...

        ## Formatting payload
        // Code here...

        try {

            $data =
                ComplainModel::with([
                    'product' => function ($query) {
                        return $query->select(
                            'tpr_id',
                            'tpr_id as id',
                            'tpr_name as name',
                            'tpr_weight as weight',
                            'tpr_expired as expired',
                            'tpr_imagePath as image',
                        );
                    },
                ])
                ->select([
                    'tpr_id',
                    'tc_id as id',
                    'tc_number as complain_number',
                    'tc_expiredCode as expired_code',
                    'tc_category as complain_category',
                    'tc_description as description',
                    'tc_receiveMedia as receive_media',
                    'tc_date as complain_date',
                    'tc_productStatus as product_status',
                    'tc_evidencePath as evidence_file',
                ]);

            // ## Filter by complain id
            if (isset($this->payload['id'])) {
                $data = $data->where('tc_id', $this->payload['id']);
            } else {
                // // ## Filter by product ids
                // if (isset($this->payload['product_id'])) {
                //     $data = $data->whereIn('tpr_id', explode(',', $this->payload['product_id']));
                // }

                // ## Filter by product_status
                if (isset($this->payload['product_status'])) {
                    $data = $data->whereIn('tc_productStatus', explode(',', $this->payload['product_status']));
                }

                // ## Filter by complain_date
                if (isset($this->payload['complain_date'])) {
                    $data = $data->where('tc_date', $this->payload['complain_date']);
                }

                // ## Filter by keyword
                if (isset($this->payload['keyword'])) {
                    $data = $data->where(function ($query) {
                        $query
                            ->where('tc_number', 'LIKE', "%{$this->payload['keyword']}%")
                            ->orWhereHas('product', function ($subQuery) {
                                $subQuery->where('tpr_name', 'LIKE', "%{$this->payload['keyword']}%");
                            });
                    });
                }
            }

            $data = $data
                ->get()
                ->map(function ($item) {
                    $item->related_schedule = [];
                    return $item;
                });


            if (isset($this->payload['id'])) {
                // ## Get staff by founded complain
                if (!$data->isEmpty()) {
                    $foundRow = $data->first();

                    $expiredCode = explode('-', $foundRow->expired_code);
                    $formatDate = DateTime::createFromFormat('Ymd', $expiredCode[0]);
                    $expiredDate = $formatDate->format('Y-m-d');

                    $payload = [
                        "expired_date" => $expiredDate,
                        "product_id" => $foundRow->product->id,
                    ];
                    $employees = (new GetSchedule(...["payload" => $payload]))->get();

                    if ($employees != '404' && count($employees) >= 1) {
                        $data->map(fn($item) => $item->related_schedule = $employees);
                    }
                }
            }

            return (object) [
                'status' => !$data->isEmpty(),
                'data' => $data->isEmpty()
                    ? null
                    : (isset($this->payload['id'])
                        ? $data->toArray()[0]
                        : $data->toArray())
            ];
        } catch (Exception $e) {

            return (object) [
                'status' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ];
        }
    }
}
