<?php

namespace App\Http\Controllers\REST\V1\Manage\Account;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;

class Update extends BaseREST
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
        'username' => '',
        'password' => '',
        'name' => '',
        'nip' => '',
        'position_id' => '',
        'phone_number' => '',
        'digital_signature' => 'file|mimes:jpeg,jpg,png,pdf,gif',
        'digital_initials' => 'file|mimes:jpeg,jpg,png,pdf,gif',
        'status' => 'in:ACTIVE,INACTIVE',
    ];

    /**
     * @var array Property that contains the privilege data
     */
    protected $privilegeRules = [
        'ACCOUNT_MANAGE_MODIFY'
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

        // Make sure account id available
        if (!DBRepo::checkAccountId($this->payload['id'])) {
            return $this->error(
                (new Errors)
                    ->setMessage(404, 'Account id not available')
                    ->setReportId('MAU1')
            );
        }

        // Make sure username not duplicate
        if (isset($this->payload['username'])) {
            if (!DBRepo::checkUsernameDuplicate($this->payload['username'], $this->payload['id'])) {
                return $this->error(
                    (new Errors)
                        ->setMessage(409, 'Username already registered not available')
                        ->setReportId('MAU2')
                );
            }
        }

        // Make sure position id is available
        if (isset($this->payload['position_id'])) {
            if (!DBRepo::checkPositionId($this->payload['id'])) {
                return $this->error(
                    (new Errors)
                        ->setMessage(409, 'Position id not found')
                        ->setReportId('MPA3')
                );
            }
        }

        return $this->update();
    }

    /** 
     * Function to insert data 
     * @return object
     */
    public function update()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);

        $update = $dbRepo->updateData();

        if ($update->status) {
            return $this->respond(200);
        }

        return $this->error(500, [
            'reason' => $update->message,
        ]);
    }
}
