<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthPos
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authData = $request->attributes->get('auth_data');
        try {

            // Get employee data
            $employees = Employee::query()
                ->with([
                    'outlets' => fn($q) => $q->select('outlets.id', 'outlets.business_id'),
                ])
                ->where('account_id', $authData['account_id'])
                ->where('is_active', true)
                ->first();

            $empData = [
                'employee_id' => $employees->id,
                'employee_name' => $employees->name,
                'outlet_id' => $employees->outlets[0]->id,
                'business_id' => $employees->outlets[0]->business_id,
            ];

            $authData = array_merge($authData, $empData);

            $request->attributes->set('auth_data', $authData);
        } catch (\Exception $e) {
        }

        return $next($request);
    }
}
