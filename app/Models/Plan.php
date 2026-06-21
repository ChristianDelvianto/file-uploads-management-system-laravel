<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    /** @use HasFactory<\Database\Factories\PlanFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'price',
        'limit_bytes',
    ];

    /**
     * The plan users that belong to the plan.
     */
    public function planUsers(): HasMany
    {
        return $this->hasMany(\App\Models\PlanUser::class, 'plan_id', 'id');
    }
    
    /**
     * The users that belong to the plan.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'plan_user', 'plan_id', 'user_id', 'id', 'id')->withTimestamps();
    }
}
