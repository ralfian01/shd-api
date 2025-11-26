<?php

namespace App\Http\Controllers\REST\V1\Shifts;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;
use App\Http\Controllers\REST\V1\Shifts\DBRepo;

class ClockOut extends BaseREST
{
    public function __construct(?array $payload = [], ?array $file = [], ?array $auth = [])
    {
        $this->payload = $payload;
        $this->file = $file;
        $this->auth = $auth;
        return $this;
    }

    // Tidak ada payload yang dibutuhkan dari klien
    protected $payloadRules = [
        'pin' => 'required|string|digits:6',
    ];

    protected $privilegeRules = [];
    protected function mainActivity()
    {
        return $this->nextValidation();
    }

    private function nextValidation()
    {
        $employeeId = $this->auth['employee_id'];
        $outletId = $this->auth['outlet_id'];

        // Validasi 1: Cek apakah karyawan terdaftar di outlet ini
        if (!DBRepo::isEmployeeAssignedToOutlet($employeeId, $outletId)) {
            return $this->error((new Errors)->setMessage(403, 'Employee is not assigned to this outlet.'));
        }

        // Validasi 2: Cek apakah PIN cocok
        if (!DBRepo::validatePin($employeeId, $this->payload['pin'])) {
            return $this->error((new Errors)->setMessage(401, 'Invalid PIN.'));
        }

        return $this->update();
    }


    public function update()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $result = $dbRepo->clockOut();
        if ($result->status) {
            return $this->respond(200, $result->data);
        }
        // Jika tidak ada shift aktif, kembalikan error 404 atau 409
        return $this->error((new Errors)->setMessage(404, $result->message));
    }
}
