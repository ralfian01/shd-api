<?php

namespace App\Http\Controllers\REST\V1\Shifts;

use App\Http\Libraries\BaseDBRepo;
use App\Models\Employee;
use App\Models\EmployeeShift;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DBRepo extends BaseDBRepo
{

    /**
     * Memeriksa apakah seorang karyawan ter-assign ke outlet tertentu.
     * @param int $employeeId
     * @param int $outletId
     * @return bool
     */
    public static function isEmployeeAssignedToOutlet(int $employeeId, int $outletId): bool
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return false;
        }
        // Gunakan relasi 'outlets()' dan cek apakah outletId ada di dalam koleksi
        return $employee->outlets()->where('outlets.id', $outletId)->exists();
    }

    /**
     * Memeriksa apakah seorang karyawan memiliki shift yang sedang aktif.
     */
    public static function hasActiveShift(int $employeeId): bool
    {
        return EmployeeShift::where('employee_id', $employeeId)
            ->where('status', 'ACTIVE')
            ->exists();
    }

    /**
     * Memvalidasi PIN karyawan.
     */
    public static function validatePin(int $employeeId, string $pin): bool
    {
        $employee = Employee::find($employeeId);
        if (!$employee || !$employee->pin) {
            return false; // Karyawan tidak ditemukan atau belum mengatur PIN
        }
        return Hash::check($pin, $employee->pin);
    }



    /**
     * Membuat record shift baru (Clock In).
     */
    public function currentShift()
    {
        $status = $this->hasActiveShift($this->auth['employee_id']);

        return (object)['status' => $status];
    }

    /**
     * Membuat record shift baru (Clock In).
     */
    public function clockIn()
    {
        try {
            return DB::transaction(function () {
                $shift = EmployeeShift::create([
                    'employee_id' => $this->auth['employee_id'],
                    'outlet_id' => $this->auth['outlet_id'],
                    'start_time' => now(),
                    'status' => 'ACTIVE',
                ]);
                return (object)['status' => true, 'data' => [
                    'id' => $shift->id
                ]];
            });
        } catch (Exception $e) {
            return (object)['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Menutup shift yang sedang aktif (Clock Out).
     */
    public function clockOut()
    {
        try {
            return DB::transaction(function () {
                $activeShift = EmployeeShift::where('employee_id', $this->auth['employee_id'])
                    ->where('status', 'ACTIVE')
                    ->first();

                if (!$activeShift) {
                    throw new Exception('No active shift found to clock out.');
                }

                $activeShift->update([
                    'end_time' => now(),
                    'status' => 'COMPLETED',
                ]);

                return (object)['status' => true, 'data' => $activeShift->id];
            });
        } catch (Exception $e) {
            return (object)['status' => false, 'message' => $e->getMessage()];
        }
    }
}
