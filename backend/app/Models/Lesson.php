<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $fillable = ['event_id', 'created_by', 'problem', 'recommendation', 'visibility', 'approved_at'];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function inspiredTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'source_lesson_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LessonAttachment::class)->orderBy('id');
    }

    public function scopeInheritedBy(Builder $query, Event $event): void
    {
        if (! $event->event_series_id || $event->eventSeries?->organization_id !== $event->organization_id) {
            $query->whereRaw('1 = 0');
            return;
        }
        $query->where('visibility', 'series')->whereNotNull('approved_at')->where('approved_at', '<=', now())
            ->whereHas('event', fn (Builder $source) => $source->where('event_series_id', $event->event_series_id)
                ->where('organization_id', $event->organization_id)->where('id', '!=', $event->id)
                ->where('starts_at', '<', $event->starts_at)->where('ends_at', '<=', $event->starts_at));
    }

    public function isInheritedBy(Event $event): bool
    {
        $source = $this->event;
        return $this->visibility === 'series' && $this->approved_at !== null && $this->approved_at->lte(now())
            && $event->event_series_id !== null && $event->eventSeries?->organization_id === $event->organization_id
            && $source->id !== $event->id && $source->organization_id === $event->organization_id
            && $source->event_series_id === $event->event_series_id
            && $source->starts_at->lt($event->starts_at) && $source->ends_at->lte($event->starts_at);
    }

}
