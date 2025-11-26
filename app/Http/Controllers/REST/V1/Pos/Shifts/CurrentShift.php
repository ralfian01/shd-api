<?php

namespace App\Http\Controllers\REST\V1\Pos\Shifts;

use App\Http\Controllers\REST\BaseREST;
use App\Http\Controllers\REST\Errors;

class CurrentShift extends BaseREST
{
    public function __construct(?array $payload = [], ?array $file = [], ?array $auth = [])
    {
        $this->payload = $payload;
        $this->file = $file;
        $this->auth = $auth;
        return $this;
    }

    // Tidak ada payload yang dibutuhkan dari klien, semua dari data otentikasi
    protected $payloadRules = [];
    protected $privilegeRules = [];

    protected function mainActivity()
    {
        return $this->nextValidation();
    }

    private function nextValidation()
    {
        return $this->get();
    }

    public function get()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $result = $dbRepo->currentShift();

        return $this->respond(200, ['has_shift' => $result->status]);
    }
}
