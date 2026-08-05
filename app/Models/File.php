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
        'visibility',
        'disk',
        'directory_path',
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
        'visibility' => 'private'
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
            $string = $this->name . $this->extension;

            // Escape full file name
            return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
     * File public link.
     */
    public function publicLink(): HasOne
    {
        return $this->hasOne(\App\Models\FilePublicLink::class, 'file_id', 'id');
    }

    /**
     * File scan status.
     */
    public function scan(): HasOne
    {
        return $this->hasOne(\App\Models\FileScan::class, 'file_id', 'id');
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
