<?php

namespace App\Support;

use App\Models\Event;

class ScheduleConflicts
{
    public function inspect(Event $event, ?int $page = null): array
    {
        $event->loadMissing(['organization', 'targets']);
        $unavailableReason = match (true) {
            $event->status === 'cancelled' => 'Event dibatalkan; pemeriksaan bentrokan tidak dijalankan.',
            ! $event->starts_at || ! $event->ends_at || $event->starts_at->gte($event->ends_at) => 'Jadwal belum valid; pemeriksaan bentrokan tidak dijalankan.',
            default => null,
        };
        $query = Event::published()->whereIn('status', ['published', 'completed'])
            ->where('id', '!=', $event->id)->whereColumn('starts_at', '<', 'ends_at')
            ->with(['organization', 'targets'])->orderBy('starts_at')->orderBy('id');
        $warnings = null;
        if ($unavailableReason === null) {
            $query->where('starts_at', '<', $event->ends_at)->where('ends_at', '>', $event->starts_at);
        } else {
            $query->whereRaw('1 = 0');
        }
        $comparisons = $query->paginate(12, page: $page);
        if ($unavailableReason === null) {
            $normalize = static fn (?string $venue): string => mb_strtolower(trim(preg_replace('/\\s+/u', ' ', $venue ?? '') ?? ''), 'UTF-8');
            $venue = $normalize($event->venue);
            $targetIds = $event->targets->modelKeys();
            $warnings = [];
            foreach ($comparisons as $comparison) {
                $types = ['time_overlap'];
                if ($venue !== '' && $venue === $normalize($comparison->venue)) {
                    $types[] = 'venue_overlap';
                }
                if (array_intersect($targetIds, $comparison->targets->modelKeys())) {
                    $types[] = 'audience_overlap';
                }
                $warnings[] = ['event' => $comparison, 'types' => $types];
            }
        }

        return compact('comparisons', 'warnings', 'unavailableReason');
    }
}
