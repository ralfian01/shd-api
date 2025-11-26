<?php

namespace App\Http\Controllers\REST\V1\Transactions\Calculate;

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
     * @var array
     * --- CONTOH PAYLOAD ---
     * {
     *     "member_id": 123,
     *     "items": [
     *         {
     *             "type": "BOOKING_TIMESLOT",
     *             "resource_id": 1,
     *             "unit_id": 2,
     *             "quantity": 2,
     *             "start_datetime": "2025-12-11 10:00:00"
     *         },
     *         {
     *             "type": "BOOKING_DYNAMIC",
     *             "resource_id": 15
     *         },
     *         {
     *             "type": "GOODS",
     *             "variant_id": 1,
     *             "quantity": 2
     *         },
     *         {
     *             "type": "SERVICE",
     *             "stock_id": 1,
     *             "quantity": 1
     *         }
     *     ]
     * }
     */
    protected $payloadRules = [
        'member_id' => 'nullable|integer|exists:members,id',
        'items' => 'required|array|min:1',
        'items.*.type' => 'required|string|in:BOOKING_TIMESLOT,BOOKING_DYNAMIC,GOODS,SERVICE',

        // Aturan untuk setiap tipe item
        'items.*.resource_id' => 'required_if:items.*.type,BOOKING_TIMESLOT,BOOKING_DYNAMIC|integer|exists:resources,resource_id',
        'items.*.unit_id' => 'required_if:items.*.type,BOOKING_TIMESLOT|integer|exists:units,unit_id',
        'items.*.quantity' => 'required_unless:items.*.type,BOOKING_DYNAMIC|numeric|min:1',
        'items.*.start_datetime' => 'required_if:items.*.type,BOOKING_TIMESLOT|date_format:Y-m-d H:i:s|after_or_equal:now',
        'items.*.variant_id' => 'required_if:items.*.type,GOODS|integer|exists:product_variants,variant_id',
        'items.*.stock_id' => 'required_if:items.*.type,SERVICE|integer|exists:service_stock,stock_id',
    ];

    protected $privilegeRules = [];

    protected function mainActivity()
    {
        return $this->nextValidation();
    }

    private function nextValidation()
    {
        return $this->calculate();
    }

    public function calculate()
    {
        $dbRepo = new DBRepo($this->payload, $this->file, $this->auth);
        $quote = $dbRepo->calculateQuote();

        if ($quote->status) {
            return $this->respond(200, $quote->data);
        }

        return $this->error((new Errors)->setMessage(409, $quote->message));
    }
}
