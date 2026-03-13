<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentResponse extends Model
{
    protected $fillable = [
        'customer_name',
        'agent_name',
        'date',
        'first_response_time',
        'average_response_time',
        'resolved_time',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}
