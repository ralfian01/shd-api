<?php

namespace App\Http\Controllers\REST\V1\Manage\Account;

use App\Http\Libraries\BaseDBRepo;
use App\Models\AccountModel;
use App\Models\BankModel;
use App\Models\PositionModel;
use App\Models\ProfileModel;
use App\Models\RoleModel;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

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
     * Function to check account id
     * @return bool
     */
    public static function checkAccountId($id)
    {
        return AccountModel::find($id) != null;
    }

    /**
     * Function to check parent id available or not
     * @return bool
     */
    public static function checkPositionId($id)
    {
        return PositionModel::find($id) != null;
    }

    /**
     * Function to check username duplication
     * @return bool
     */
    public static function checkUsernameDuplicate($username, $accountId)
    {
        $account =
            AccountModel::select('ta_id')
            ->where('ta_statusDelete', false)
            ->where('ta_username', $username)
            ->first();

        if (!$account)
            return true;

        return $account->ta_id == $accountId;
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
                ProfileModel::with([
                    'account' => function ($query) {
                        return $query->select([
                            "ta_id",
                            "ta_username as username",
                            "ta_statusActive as status"
                        ]);
                    },
                    'position' => function ($query) {
                        return $query
                            ->with(['parent' => function ($query2) {
                                return $query2->select([
                                    "tp_id",
                                    "tp_name as name"
                                ]);
                            }])
                            ->select([
                                "tp_id",
                                "tp_parentId",
                                "tp_name as name"
                            ]);
                    },
                ])
                ->select([
                    'tp_id',
                    'ta_id',
                    'ta_id as id',
                    'tpr_name as name',
                    'tpr_nip as nip',
                    'tpr_phoneNumber as phone_number',
                    'tpr_digitalSignature as digital_signature',
                    'tpr_digitalInitials as digital_initials'
                ]);

            if (isset($this->payload['id'])) {
                $data = $data->where('ta_id', $this->payload['id']);
            } else {
                // if (isset($this->payload['parent_id'])) {
                //     if ($this->payload['parent_id'] == 0) {
                //         $data = $data->where('tp_parentId', null);
                //     } else {
                //         $data = $data->where('tp_parentId', $this->payload['parent_id']);
                //     }
                // }
            }

            $data = $data->get();

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
            ];
        }
    }

    /**
     * Function to insert data from database
     * @return object|bool
     */
    public function insertData()
    {
        ## Formatting additional data which not payload
        // # Generate UUID
        $uuid = Uuid::uuid4();

        // # Get role id from role code
        $role = RoleModel::select('tr_id')
            ->where('tr_code', '=', 'USER')
            ->get();

        if ($role->isEmpty()) {
            return (object) [
                'status' => false,
                'message' => 'There is no role code named "USER"'
            ];
        }

        $roleId = $role[0]['tr_id'];

        ## Formatting payload
        // Code here...

        try {

            return DB::transaction(function () use ($uuid, $roleId) {

                // If id found and Delete keys that have a null value
                $dbPayload = Arr::whereNotNull([
                    'ta_uuid' => $uuid,
                    'ta_username' => $this->payload['username'] ?? null,
                    'ta_password' => $this->payload['password'] ?? null,
                    // 'ta_password' => isset($this->payload['password']) ? hash('sha256', $this->payload['password']) : null,
                    'ta_statusActive' => isset($this->payload['status']) ? $this->payload['status'] == "ACTIVE" : null,
                    'tr_id' => $roleId
                ]);

                ## Insert position
                $insertData = AccountModel::create($dbPayload);

                if (!$insertData) {
                    $tableName = AccountModel::tableName();
                    throw new Exception("Failed when insert data into table \"{$tableName}\"");
                }

                // ## Insert account profile
                if (isset($this->file['digital_signature']))
                    $this->payload['digital_signature'] = ($this->file['digital_signature']->move(public_path('uploads'), date('YmdHis') . "_" . uniqid() . ".webp"))->getFilename();

                if (isset($this->file['digital_initials']))
                    $this->payload['digital_initials'] = ($this->file['digital_initials']->move(public_path('uploads'), date('YmdHis') . "_" . uniqid() . ".webp"))->getFilename();

                // If id found and Delete keys that have a null value
                $dbPayload = Arr::whereNotNull([
                    'ta_id' => $insertData->ta_id,
                    'tpr_name' => $this->payload['name'] ?? null,
                    'tpr_nip' => $this->payload['nip'] ?? null,
                    'tpr_digitalSignature' => $this->payload['digital_signature'] ?? null,
                    'tpr_digitalInitials' => $this->payload['digital_initials'] ?? null,
                    'tpr_phoneNumber' => format_phone_number($this->payload['phone_number']) ?? null,
                    'tp_id' => $this->payload['position_id'] ?? null,
                ]);

                ## Insert profile
                $insertProfile = ProfileModel::create($dbPayload);

                if (!$insertProfile) {
                    $tableName = ProfileModel::tableName();
                    throw new Exception("Failed when insert data into table \"{$tableName}\"");
                }

                // Return transaction status
                return (object) [
                    'status' => true,
                ];
            });
        } catch (Exception $e) {

            return (object) [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Function to update data from database
     * @return object|bool
     */
    public function updateData()
    {
        ## Formatting additional data which not payload
        // # Get profile id from account id
        $profile = ProfileModel::where('ta_id', $this->payload['id'])->first();

        ## Formatting payload
        // Code here...

        try {

            return DB::transaction(function () use ($profile) {

                // If id found and Delete keys that have a null value
                $dbPayload = Arr::whereNotNull([
                    'ta_username' => $this->payload['username'] ?? null,
                    'ta_password' => $this->payload['password'] ?? null,
                    // 'ta_password' => isset($this->payload['password']) ? hash('sha256', $this->payload['password']) : null,
                    'ta_statusActive' => isset($this->payload['status']) ? $this->payload['status'] == "ACTIVE" : null,
                ]);

                ## Update data
                $updateData = AccountModel::find($this->payload['id'])->update($dbPayload);

                if (!$updateData) {
                    $tableName = AccountModel::tableName();
                    throw new Exception("Failed when update data into table \"{$tableName}\"");
                }


                // ## Insert account profile
                if (isset($this->file['digital_signature']))
                    $this->payload['digital_signature'] = ($this->file['digital_signature']->move(public_path('uploads'), date('YmdHis') . "_" . uniqid() . ".webp"))->getFilename();

                if (isset($this->file['digital_initials']))
                    $this->payload['digital_initials'] = ($this->file['digital_initials']->move(public_path('uploads'), date('YmdHis') . "_" . uniqid() . ".webp"))->getFilename();

                // If id found and Delete keys that have a null value
                $dbPayload = Arr::whereNotNull([
                    'tpr_name' => $this->payload['name'] ?? null,
                    'tpr_nip' => $this->payload['nip'] ?? null,
                    'tpr_digitalSignature' => $this->payload['digital_signature'] ?? null,
                    'tpr_digitalInitials' => $this->payload['digital_initials'] ?? null,
                    'tpr_phoneNumber' => format_phone_number($this->payload['phone_number']) ?? null,
                    'tp_id' => $this->payload['position_id'] ?? null,
                ]);

                ## Update profile
                $updateProfile = ProfileModel::find($profile->tpr_id)->update($dbPayload);

                if (!$updateProfile) {
                    $tableName = ProfileModel::tableName();
                    throw new Exception("Failed when insert data into table \"{$tableName}\"");
                }

                // Return transaction status
                return (object) [
                    'status' => true,
                ];
            });
        } catch (Exception $e) {

            return (object) [
                'status' => false,
                'message' => [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile()
                ]
            ];
        }
    }

    /**
     * Function to insert data from database
     * @return object|bool
     */
    public function deleteData()
    {
        ## Formatting additional data which not payload
        // Code here...

        ## Formatting payload
        // Code here...

        try {

            return DB::transaction(function () {

                ## Delete valid region
                $deleteData = AccountModel::find($this->payload['id'])->delete();

                if (!$deleteData) {
                    $tableName = AccountModel::tableName();
                    throw new Exception("Failed when delete data into table \"{$tableName}\"");
                }

                // Return transaction status
                return (object) [
                    'status' => true,
                ];
            });
        } catch (Exception $e) {

            return (object) [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
