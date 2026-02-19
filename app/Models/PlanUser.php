<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanUser extends Model
{
    /** @use HasFactory<\Database\Factories\PlanUserFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'plan_id',
        'user_id',
    ];

    /**
     * PlanUser belongs to Plan
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Plan::class, 'plan_id', 'id');
    }

    /**
     * PlanUser belongs to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }
}
