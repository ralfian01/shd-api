<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveSession extends Model
{
    use HasFactory;
    protected $table = 'active_sessions';
    protected $primaryKey = 'session_id';
    protected $fillable = ['resource_id', 'start_time', 'end_time', 'status', 'final_duration_minutes', 'final_price'];
}
