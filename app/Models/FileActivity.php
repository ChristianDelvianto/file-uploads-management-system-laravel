<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileActivity extends Model
{
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'action',
        'ip_address',
        'user_agent',
        'file_id',
        'user_id',
    ];

    /**
     * Get the file
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(\App\Models\File::class, 'file_id', 'id');
    }

    /**
     * Belongs to user, null if guest
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }
}
