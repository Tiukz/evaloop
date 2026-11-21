<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WorkspaceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate(['q' => 'nullable|string|max:120', 'page' => 'nullable|integer|min:1']);
        $search = trim($filters['q'] ?? '');
        $userId = $request->user()->id;
        $visible = Event::where(fn (Builder $scope) => $scope
            ->whereHas('memberships', fn (Builder $members) => $members->where('user_id', $userId))
            ->orWhereHas('organization.memberships', fn (Builder $members) => $members
                ->active()->where('user_id', $userId)->where('role', 'admin')))
            ->with('organization:id,name');

        $featured = (clone $visible)->whereIn('status', ['draft', 'published'])
            ->where('ends_at', '>', now())->orderBy('starts_at')->orderBy('id')->first()
            ?? (clone $visible)->orderByDesc('starts_at')->orderByDesc('id')->first();
        $events = $visible->when($search !== '', fn (Builder $query) =>
            $query->where(fn (Builder $match) => $match->whereLike('title', "%{$search}%")
                ->orWhereHas('organization', fn (Builder $organization) => $organization->whereLike('name', "%{$search}%"))))
            ->orderBy('starts_at')->orderBy('id')->paginate(12);

        return $this->respond([
            'data' => $events->getCollection()->map(fn (Event $event) => $this->identity($event)),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'total' => $events->total(),
            ],
            'featured' => $featured ? $this->identity($featured) : null,
        ]);
    }

    public function show(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('viewWorkspace', $event);
        $event->load(['organization:id,name', 'eventSeries:id,name']);
        $members = $event->memberships();
        $coordinator = (clone $members)->where('role', 'coordinator')->with('user:id,name')->first();
        $role = (clone $members)->where('user_id', $request->user()->id)->value('role');
        $counts = $event->tasks()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');

        return $this->respond(['data' => $this->identity($event) + [
            'ends_at' => $event->ends_at->toISOString(),
            'venue' => $event->venue,
            'series' => $event->eventSeries?->name,
            'has_poster' => (bool) $event->poster_path,
            'can_manage' => $request->user()->can('manage', $event),
            'committee' => [
                'total' => $members->count(),
                'coordinator' => $coordinator?->user?->name,
                'role' => $role ?? 'organization_admin',
            ],
            'task_counts' => [
                'todo' => (int) ($counts['todo'] ?? 0),
                'in_progress' => (int) ($counts['in_progress'] ?? 0),
                'done' => (int) ($counts['done'] ?? 0),
            ],
        ]]);
    }

    private function identity(Event $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'organization' => $event->organization->name,
            'status' => $event->status,
            'starts_at' => $event->starts_at->toISOString(),
        ];
    }

    private function respond(array $data): JsonResponse
    {
        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }
}
