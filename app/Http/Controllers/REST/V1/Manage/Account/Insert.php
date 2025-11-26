<?php

namespace App\Http\Controllers\REST\V1\Manage\Account;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;

class Insert extends BaseREST
{
    public function __construct(
        ?array $payload = [],
        ?array $file = [],
        ?array $auth = [],
    ) {

        $this->payload = $payload;
        $this->file = $file;
        $this->auth = $auth;
        return $this;
    }

    /**
     * @var array Property that contains the payload rules
     */
    protected $payloadRules = [
        'username' => 'required',
        'password' => 'required',
        'name' => 'required',
        'nip' => 'required',
        'position_id' => 'required',
        'phone_number' => '',
        'digital_signature' => 'file|mimes:jpeg,jpg,png,pdf,gif',
        'digital_initials' => 'file|mimes:jpeg,jpg,png,pdf,gif',
        'status' => 'required|in:ACTIVE,INACTIVE',
    ];

    /**
     * @var array Property that contains the privilege data
     */
    protected $privilegeRules = [
        'ACCOUNT_MANAGE_ADD'
    ];


    /**
     * The method that starts the main activity
     * @return null
     */
    protected function mainActivity()
    {
        return $this->nextValidation();
    }

    /**
     * Handle the next step of payload validation
     * @return void
     */
    private function nextValidation()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);

        // Make sure username not duplicate
        if (!DBRepo::checkUsernameDuplicate($this->payload['username'], $this->auth['account_id'])) {
            return $this->error(
                (new Errors)
                    ->setMessage(409, 'Username already registered not available')
                    ->setReportId('MAI1')
            );
        }

        // Make sure position id is available
        if (!DBRepo::checkPositionId($this->payload['position_id'])) {
            return $this->error(
                (new Errors)
                    ->setMessage(409, 'Position id not available')
                    ->setReportId('MAI2')
            );
        }

        return $this->insert();
    }

    /** 
     * Function to insert data 
     * @return object
     */
    public function insert()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);

        $insert = $dbRepo->insertData();

        if ($insert->status) {
            return $this->respond(200);
        }

        return $this->error(500, [
            'reason' => $insert->message
        ]);
    }
}
