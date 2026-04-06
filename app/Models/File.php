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
        'visibility',
        'category',
        'extension',
        'mime_type',
        'name',
        'bytes_size',
        'thumbnail_name',
        'storage_name',
        'disk',
        'user_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'id',
        'user_id',
        'disk',
    ];

    /**
     * The model's default values for attributes.
     *
     * @return array<string, any>
     */
    protected $attributes = [
        'visibility' => 'private',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * Define route key name for route model binding
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * File has many logs
     */
    public function logs(): HasMany
    {
        return $this->hasMany(\App\Models\FileLog::class, 'file_id', 'id');
    }

    /**
     * File belongs to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }
}
