<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileLog extends Model
{
    /** @use HasFactory<\Database\Factories\FileAuditLogFactory> */
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
        'ip_address',
    ];

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
