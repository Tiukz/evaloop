<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventLeaderRequest;
use App\Http\Requests\EventMemberRequest;
use App\Models\Event;
use App\Models\EventMember;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EventMemberController extends Controller
{
    public function index(Event $event): View
    {
        Gate::authorize('viewWorkspace', $event);
        $memberships = $event->memberships()->with('user')->orderBy('id')->paginate(20);

        return view('events.members.index', compact('event', 'memberships'));
    }

    public function create(Event $event): View
    {
        Gate::authorize('manage', $event);

        return view('events.members.form', ['event' => $event, 'membership' => new EventMember]);
    }

    public function edit(Event $event, EventMember $membership): View
    {
        Gate::authorize('manage', $event);
        abort_if($membership->role === 'coordinator', 403);

        return view('events.members.form', compact('event', 'membership'));
    }

    public function store(EventMemberRequest $request, Event $event): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $event) {
            $event = Event::lockForUpdate()->findOrFail($event->id);
            Gate::authorize('manage', $event);
            if ($event->memberships()->where('user_id', $request->validated('user_id'))->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['user_id' => 'Pengguna sudah berada dalam kepanitiaan.']);
            }
            $event->memberships()->create(['user_id' => $request->validated('user_id'), 'role' => 'member']);
        });
        return redirect()->route('workspace.members', $event)->with('status', 'Panitia berhasil ditambahkan.');
    }

    public function update(EventMemberRequest $request, Event $event, EventMember $membership): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($event, $membership) {
            $event = Event::lockForUpdate()->findOrFail($event->id);
            Gate::authorize('manage', $event);
            $membership = $event->memberships()->lockForUpdate()->findOrFail($membership->id);
            abort_if($membership->role === 'coordinator', 403);
            $membership->update(['role' => 'member']);
        });
        return redirect()->route('workspace.members', $event)->with('status', 'Keanggotaan panitia diperbarui.');
    }

    public function destroy(Event $event, EventMember $membership): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($event, $membership) {
            $event = Event::lockForUpdate()->findOrFail($event->id);
            Gate::authorize('manage', $event);
            $membership = $event->memberships()->lockForUpdate()->findOrFail($membership->id);
            abort_if($membership->role === 'coordinator', 403);
            if ($event->tasks()->where('assignee_user_id', $membership->user_id)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['membership' => 'Anggota masih direferensikan tugas. Alihkan PIC secara eksplisit sebelum mengeluarkan anggota; riwayat tugas tidak dihapus.']);
            }
            $membership->delete();
        });
        return redirect()->route('workspace.members', $event)->with('status', 'Anggota dikeluarkan dari kepanitiaan.');
    }

    public function leader(EventLeaderRequest $request, Event $event): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $event) {
            $event = Event::lockForUpdate()->findOrFail($event->id);
            Gate::authorize('assignLeader', $event);
            $event->memberships()->where('role', 'coordinator')->update(['role' => 'member']);
            $event->memberships()->updateOrCreate(['user_id' => $request->validated('user_id')], ['role' => 'coordinator']);
        });
        return redirect()->route('workspace.members', $event)->with('status', 'Ketua panitia berhasil ditetapkan.');
    }
}
