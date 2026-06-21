<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'disk',
        'directory_path',
        'visibility',
        'scan_status',
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
        'directory_path',
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
        'scan_status' => 'pending'
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
     * Get file full name with extension.
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(function () {
            return "{$this->name}.{$this->extension}";
        });
    }

    /**
     * Define route key name for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * File has many activities.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(\App\Models\FileActivity::class, 'file_id', 'id');
    }

    /**
     * File public link
     */
    public function publicLink(): HasOne
    {
        return $this->hasOne(\App\Models\FilePublicLink::class, 'file_id', 'id');
    }

    /**
     * Get the users that the file is shared with.
     */
    public function shared(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'file_shared', 'file_id', 'user_id', 'id', 'id');
    }

    /**
     * The user that has the file.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }
}
