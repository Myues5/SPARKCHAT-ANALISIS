<?php
    namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use App\Models\User;

    /**
     * @property int $id
     * @property string $chat_id
     * @property string $user_id
     * @property string $platform
     * @property string $received_at
     * @property string $rating
     * @property string $cs_id
     * @property-read string $name
     * @property-read string $photo
     * @property-read string $role
     * @property-read string $email
     * @property-read string $status
     * @property-read \Carbon\Carbon|null $last_status_update
     */
    class SatisfactionRating extends Model
    {
        use HasFactory;

        protected $table = 'satisfaction_ratings';
        public $timestamps = false;

        protected $fillable = [
            'id',
            'chat_id',
            'user_id',
            'platform',
            'received_at',
            'rating',
            'cs_id',
        ];

        public function cs()
        {
            return $this->belongsTo(User::class, 'cs_id');
        }

        public function user()
        {
            return $this->belongsTo(User::class, 'user_id');
        }
    }
?>
