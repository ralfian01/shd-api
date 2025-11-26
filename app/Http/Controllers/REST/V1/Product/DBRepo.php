<?php

namespace App\Http\Controllers\REST\V1\Product;

use App\Http\Libraries\BaseDBRepo;
use App\Models\ProductModel;
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
     * Function to check product id
     * @return bool
     */
    public static function checkProductId($bankId)
    {
        return ProductModel::find($bankId) != null;
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

            $data = ProductModel::select([
                "tpr_id as id",
                "tpr_name as name",
                "tpr_weight as weight",
                "tpr_expired as expired_duration",
                "tpr_imagePath as image",
            ]);

            if (isset($this->payload['name'])) {
                $data = $data->where('tpr_name', 'LIKE', "%{$this->payload['name']}%");
            }

            $data = $data->get();

            return (object) [
                'status' => !$data->isEmpty(),
                'data' => $data->isEmpty() ? null : $data->toArray()
            ];
        } catch (Exception $e) {

            return (object) [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
