<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FilePublicLink extends Model
{
    /** @use HasFactory<\Database\Factories\FilePublicLinkFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'link_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [];

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
        return [];
    }

    /**
     * Get the file that owns the public link.
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id', 'id');
    }
}
