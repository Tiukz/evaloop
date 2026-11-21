<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\ScheduleConflicts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ScheduleConflictController extends Controller
{
    public function __invoke(Request $request, Event $event, ScheduleConflicts $conflicts): JsonResponse
    {
        Gate::authorize('manage', $event);
        $request->validate(['page' => 'nullable|integer|min:1']);

        ['comparisons' => $page, 'warnings' => $warnings, 'unavailableReason' => $unavailable] = $conflicts->inspect($event);

        return response()->json([
            'event' => $this->schedule($event) + ['target_programs' => $event->targets->pluck('name')->values()],
            'warnings' => array_map(fn (array $warning) => [
                'event' => $this->schedule($warning['event']),
                'reasons' => array_map(fn (string $type) => $this->reason($type), $warning['types']),
            ], $warnings ?? []),
            'unavailable_reason' => $unavailable,
            'meta' => [
                'total' => $page->total(),
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
            ],
        ])->header('Cache-Control', 'private, no-store');
    }

    private function schedule(Event $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'organization' => $event->organization->name,
            'status' => $event->status,
            'starts_at' => $event->starts_at?->toISOString(),
            'ends_at' => $event->ends_at?->toISOString(),
            'venue' => $event->venue,
        ];
    }

    private function reason(string $type): array
    {
        [$label, $description] = match ($type) {
            'time_overlap' => ['Waktu', 'Waktu pelaksanaan kedua kegiatan beririsan.'],
            'venue_overlap' => ['Lokasi', 'Kedua kegiatan mencantumkan lokasi yang sama.'],
            'audience_overlap' => ['Target', 'Kedua kegiatan memiliki target program studi yang beririsan.'],
        };

        return ['type' => $type, 'label' => $label, 'description' => $description];
    }
}
