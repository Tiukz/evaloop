<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Event extends Model
{
    protected $fillable = ['organization_id', 'event_series_id', 'title', 'description', 'starts_at', 'ends_at', 'venue', 'status', 'published_at'];

    protected $hidden = ['poster_path'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'published_at' => 'datetime'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function eventSeries(): BelongsTo
    {
        return $this->belongsTo(EventSeries::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(EventMember::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_members')->withPivot('role')->withTimestamps();
    }

    public function targets(): BelongsToMany
    {
        return $this->belongsToMany(StudyProgram::class, 'event_targets');
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at')->whereIn('status', ['published', 'completed', 'cancelled']);
    }

    public function canBeCompleted(): bool
    {
        return $this->status === 'published' && $this->published_at !== null;
    }
}
