<?php

namespace App\Support;

use App\Models\Event;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class EventRadar
{
    public function query(array $filters, CarbonImmutable $radarNow): Builder
    {
        $query = Event::published()->with(['organization.faculty', 'organization.studyProgram']);
        if (isset($filters['q'])) {
            $keyword = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['q']);
            $query->whereRaw("title LIKE ? ESCAPE '!'", ['%'.$keyword.'%']);
        }
        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }
        // Parentless university organizations remain discoverable through explicit target programs.
        foreach (['faculty_id', 'study_program_id'] as $key) {
            if (isset($filters[$key])) {
                $value = $filters[$key];
                $query->where(function (Builder $scope) use ($key, $value) {
                    $scope->whereHas('organization', fn (Builder $org) => $org->where($key, $value))
                        ->orWhereHas('targets', fn (Builder $targets) => $targets->where($key === 'faculty_id' ? 'faculty_id' : 'study_programs.id', $value));
                });
            }
        }
        // Inclusive WITA calendar dates become a half-open UTC interval.
        if (isset($filters['date_from'])) {
            $query->where('ends_at', '>', CarbonImmutable::parse($filters['date_from'], 'Asia/Makassar')->startOfDay()->utc());
        }
        if (isset($filters['date_to'])) {
            $query->where('starts_at', '<', CarbonImmutable::parse($filters['date_to'], 'Asia/Makassar')->startOfDay()->addDay()->utc());
        }
        // Discovery period is read-only; publication eligibility stays in scopePublished.
        $period = $filters['period'] ?? 'all';
        if ($period === 'upcoming') {
            $query->where('status', 'published')->where('ends_at', '>', $radarNow);
        } elseif ($period === 'history') {
            $query->where(fn (Builder $history) => $history
                ->whereIn('status', ['completed', 'cancelled'])->orWhere('ends_at', '<=', $radarNow));
        }

        return $query;
    }

    public function paginate(Builder $query, CarbonImmutable $radarNow, array $filters): LengthAwarePaginator
    {
        // Rank ongoing/upcoming before history BEFORE pagination. Recent history comes first.
        return $query
            ->orderByRaw("CASE WHEN status = 'published' AND ends_at > ? THEN 0 ELSE 1 END", [$radarNow])
            ->orderByRaw("CASE WHEN status = 'published' AND ends_at > ? THEN starts_at END ASC", [$radarNow])
            ->orderByDesc('starts_at')->orderBy('id')->paginate(12)->appends($filters);
    }
}
