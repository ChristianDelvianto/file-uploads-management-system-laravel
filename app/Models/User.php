<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role',
        'used_bytes',
        'name',
        'email',
        'password',
        'last_delete_all_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'role',
        'password',
        // 'remember_token'
    ];

    /**
     * The model's default values for attributes.
     *
     * @return array<string, any>
     */
    protected $attributes = [
        'role' => 'user',
        'used_bytes' => 0,
        'last_delete_all_at' => null
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime'
        ];
    }

    /**
     * User has many activities
     */
    public function activities(): HasMany
    {
        return $this->hasMany(\App\Models\UserActivity::class, 'user_id', 'id');
    }

    /**
     * User has many File
     */
    public function files(): HasMany
    {
        return $this->hasMany(\App\Models\File::class, 'user_id', 'id');
    }

    /**
     * User has one Plan
     */
    public function plan(): HasOne
    {
        return $this->hasOne(\App\Models\PlanUser::class, 'user_id', 'id');
    }
}
