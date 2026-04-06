<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserLog extends Model
{
    /** @use HasFactory<\Database\Factories\UserLogFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'action',
        'file_id',
        'user_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'file_id',
        'user_id',
    ];

    /**
     * Edit log entries related to this user log
     */
    public function edits(): HasMany
    {
        return $this->hasMany(\App\Models\UserLogEdit::class, 'user_log_id', 'id');
    }

    /**
     * Get the file
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(\App\Models\File::class, 'file_id', 'id');
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }
}
