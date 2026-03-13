<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'status',
        'source',
        'cs_id',
        'customer_id',
        'customer_email',
        'created_at',
        'last_updated',
        'solved_at',
        'taken_at',
        'reopened_at',
        'phone'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'last_updated' => 'datetime',
        'solved_at' => 'datetime',
        'taken_at' => 'datetime',
        'reopened_at' => 'datetime'
    ];

    public function messages()
    {
        return $this->hasMany(Message::class, 'room_id', 'id');
    }

    // Method untuk mendapatkan CS yang menangani room ini
    public function getCSAttribute()
    {
        return $this->messages()->fromCS()->first();
    }
}
