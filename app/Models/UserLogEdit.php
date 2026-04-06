<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLogEdit extends Model
{
    /** @use HasFactory<\Database\Factories\UserLogEditFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'action',
        'field',
        'old_value',
        'new_value',
        'user_log_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [];

    /**
     * Get the user log
     */
    public function log(): BelongsTo
    {
        return $this->belongsTo(\App\Models\UserLog::class, 'user_log_id', 'id');
    }
}
