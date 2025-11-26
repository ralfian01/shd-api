<?php

namespace App\Http\Controllers\REST\V1\Manage\Accounts;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;

class Delete extends BaseREST
{
    public function __construct(?array $p = [], ?array $f = [], ?array $a = [])
    {
        $this->payload = $p;
        $this->file = $f;
        $this->auth = $a;
        return $this;
    }
    protected $payloadRules = ['id' => 'required|integer|exists:account,id'];
    protected $privilegeRules = [];
    protected function mainActivity()
    {
        return $this->nextValidation();
    }
    private function nextValidation()
    {
        if (!DBRepo::isAccountDeletable($this->payload['id'])) {
            return $this->error((new Errors)->setMessage(403, 'This account is protected and cannot be deleted.'));
        }
        if (DBRepo::isAccountInUse($this->payload['id'])) {
            return $this->error((new Errors)->setMessage(409, 'Cannot delete account: It is currently assigned to an employee.'));
        }
        return $this->delete();
    }
    public function delete()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $r = $dbRepo->deleteData();
        if ($r->status) {
            return $this->respond(200);
        }
        return $this->error(500, ['reason' => $r->message]);
    }
}
