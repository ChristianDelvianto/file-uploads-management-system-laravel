<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    /** @use HasFactory<\Database\Factories\FileFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'status',
        'visibility',
        'is_scanned',
        'disk',
        'category',
        'extension',
        'mime_type',
        'name',
        'duration',
        'bytes_size',
        'storage_name',
        'thumbnail_name',
        'user_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'id',
        'disk',
        'user_id'
    ];

    /**
     * The model's default values for attributes.
     *
     * @return array<string, any>
     */
    protected $attributes = [
        'status' => 'completed',
        'visibility' => 'private',
        'is_scanned' => false
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_scanned' => 'boolean',
            'last_action_at' => 'datetime'
        ];
    }

    /**
     * Define route key name for route model binding
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * File has many activities
     */
    public function activities(): HasMany
    {
        return $this->hasMany(\App\Models\FileActivity::class, 'file_id', 'id');
    }

    /**
     * 
     */
    // public function shared(): BelongsToMany
    // {
    //     return $this->belongsToMany(\App\Models\User::class, 'file_shared', 'file_id', 'user_id', 'id', 'id');
    // }

    /**
     * File belongs to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }
}
