<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * @property int $id
 * @property int $room_id
 * @property string $message
 * @property int $sender_id
 * @property string $sender_username
 * @property string $role
 * @property int|null $reply_to
 * @property string|null $reply_text
 * @property string|null $reply_sender
 * @property string $type
 * @property \Illuminate\Support\Carbon $timestamp
 * @property array|null $attachments
 * @property bool $deleted
 * @property int|null $session_id
 * @property bool $read
 * @property int|null $response_time
 */

class Message extends Model
{
    use HasFactory;

    protected $table = 'messages';
    protected $primaryKey = 'id';
    public $incrementing = false; // Set to false for non-incrementing primary key
    protected $keyType = 'string'; // Set to string for varchar primary key
    public $timestamps = false; // Table doesn't have created_at/updated_at

    protected $fillable = [
        'id', // Include id back since it's not auto-increment
        'room_id',
        'message',
        'sender_id',
        'sender_username',
        'role',
        'reply_to',
        'reply_text',
        'reply_sender',
        'type',
        'timestamp',
        'attachments',
        'deleted',
        'session_id',
        'read',
        'response_time'
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'attachments' => 'json',
        'read' => 'boolean',
        'deleted' => 'boolean',
        'response_time' => 'integer' // <-- Cast ke integer
    ];

    public function chatRoom()
    {
        return $this->belongsTo(ChatRoom::class, 'room_id', 'id');
    }

    // Scope untuk pesan yang sudah dibaca
    public function scopeRead($query)
    {
        return $query->where('read', true);
    }

    // Scope untuk pesan yang belum dibaca
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    // Scope untuk CS (customer service)
    public function scopeFromCS($query)
    {
        return $query->where('role', 'cs');
    }

    /**
     * Accessor: Hitung response time dinamis jika belum disimpan di database
     */
    public function getResponseTimeAttribute($value)
    {
        if (!is_null($value)) {
            return $value;
        }

        if ($this->role !== 'cs' || !$this->timestamp) {
            return null;
        }

        $previousCustomerMessage = self::where('room_id', $this->room_id)
            ->where('role', 'customer')
            ->where('timestamp', '<', $this->timestamp)
            ->orderBy('timestamp', 'desc')
            ->first();

        if ($previousCustomerMessage && $previousCustomerMessage->timestamp) {
            $responseTime = $this->timestamp->diffInMinutes($previousCustomerMessage->timestamp);
            $this->updateQuietly(['response_time' => $responseTime]); // Simpan tanpa event
            return $responseTime;
        }

        return null;
    }

    /**
     * Cek apakah ini response cepat
     */
    public function isFastResponse($threshold = 5)
    {
        $responseTime = $this->response_time;
        return $responseTime !== null && $responseTime <= $threshold;
    }

    /**
     * Cek apakah ini response lambat
     */
    public function isSlowResponse($threshold = 5)
    {
        $responseTime = $this->response_time;
        return $responseTime !== null && $responseTime > $threshold;
    }
}
