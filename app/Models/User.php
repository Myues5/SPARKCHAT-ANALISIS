<?php
    namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Foundation\Auth\User as Authenticatable;
    use Illuminate\Notifications\Notifiable;
    use Illuminate\Support\Str;
    use Illuminate\Support\Carbon;

    /**
     * App\Models\User
     *
     * @property string $id
     * @property string|null $username
     * @property string|null $email
     * @property string|null $phone
     * @property string|null $password
     * @property string|null $role
     * @property string|null $status
     * @property bool $is_active
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property Carbon|null $email_verified_at
     * @property Carbon|null $last_status_update
     * @property string|null $remember_token
     */
    class User extends Authenticatable
    {
        use HasFactory, Notifiable;

        public $incrementing = false;
        protected $keyType = 'string';

        protected $fillable = [
            'id',
            'username',
            'email',
            'phone',
            'password',
            'role',
            'status',
            'is_active',
            'created_at',
            'updated_at',
            'last_status_update',
            'remember_token',
            'email_verified_at',
        ];

        protected $hidden = [
            'password',
            'remember_token',
        ];

        protected $casts = [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'last_status_update' => 'datetime',
        ];

        protected static function boot()
        {
            parent::boot();
            static::creating(function ($model) {
                if (empty($model->id)) {
                    $model->id = (string) Str::uuid();
                }
            });
        }
    }
?>