<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class OrganizationMember extends Model
{
    protected $fillable = ['organization_id', 'user_id', 'role', 'status', 'joined_at', 'ended_at'];

    protected function casts(): array
    {
        return ['joined_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): void
    {
        $now = now();
        $query->where('status', 'active')->where('joined_at', '<=', $now)
            ->where(fn (Builder $dates) => $dates->whereNull('ended_at')->orWhere('ended_at', '>', $now));
    }
}
