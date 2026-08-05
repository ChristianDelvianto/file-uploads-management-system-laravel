<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'photo',
        'name',
        'email',
        'password'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password'
        // 'remember_token'
    ];

    /**
     * The model's default values for attributes.
     *
     * @return array<string, any>
     */
    protected $attributes = [];

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
     * User has many File.
     */
    public function files(): HasMany
    {
        return $this->hasMany(\App\Models\File::class, 'user_id', 'id');
    }

    /**
     * User has one Plan.
     */
    public function plan(): HasOne
    {
        return $this->hasOne(\App\Models\PlanUser::class, 'user_id', 'id');
    }

    /**
     * User has one Quota.
     */
    public function quota(): HasOne
    {
        return $this->hasOne(\App\Models\UserQuota::class, 'user_id', 'id');
    }

    /**
     * User belongs to many File.
     */
    public function shared(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\File::class, 'file_shared', 'user_id', 'file_id', 'id', 'id');
    }

    /**
     * User has many Upload.
     */
    public function uploads(): HasMany
    {
        return $this->hasMany(\App\Models\Upload::class, 'user_id', 'id');
    }
}
