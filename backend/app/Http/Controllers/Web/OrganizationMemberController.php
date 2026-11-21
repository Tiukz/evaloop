<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizationMemberRequest;
use App\Models\Organization;
use App\Models\OrganizationMember;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrganizationMemberController extends Controller
{
    public function index(Organization $organization): View
    {
        Gate::authorize('manage', $organization);
        $memberships = $organization->memberships()->with('user')->orderBy('id')->paginate(20);
        $activeIds = $organization->memberships()->active()->whereIn('id', $memberships->modelKeys())->pluck('id');

        return view('organizations.members.index', compact('organization', 'memberships', 'activeIds'));
    }

    public function create(Organization $organization): View
    {
        Gate::authorize('manage', $organization);

        return view('organizations.members.form', ['organization' => $organization, 'membership' => new OrganizationMember]);
    }

    public function edit(Organization $organization, OrganizationMember $membership): View
    {
        Gate::authorize('manage', $organization);

        return view('organizations.members.form', compact('organization', 'membership'));
    }

    private function memberData(OrganizationMemberRequest $request): array
    {
        $data = $request->safe()->only(['user_id', 'role', 'status']);
        foreach (['joined_at', 'ended_at'] as $key) {
            $data[$key] = $request->validated($key) ? \Carbon\CarbonImmutable::parse($request->validated($key), 'Asia/Makassar')->utc() : null;
        }
        return $data;
    }

    public function store(OrganizationMemberRequest $request, Organization $organization): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $organization) {
            Organization::lockForUpdate()->findOrFail($organization->id);
            Gate::authorize('manage', $organization);
            if ($organization->memberships()->where('user_id', $request->validated('user_id'))->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['user_id' => 'Pengguna sudah menjadi anggota.']);
            }
            $organization->memberships()->create($this->memberData($request));
        });
        return redirect()->route('organizations.members', $organization)->with('status', 'Anggota berhasil ditambahkan.');
    }

    public function update(OrganizationMemberRequest $request, Organization $organization, OrganizationMember $membership): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $organization, $membership) {
            Organization::lockForUpdate()->findOrFail($organization->id);
            Gate::authorize('manage', $organization);
            $membership = $organization->memberships()->lockForUpdate()->findOrFail($membership->id);
            $wasAdmin = $membership->role === 'admin' && $organization->memberships()->active()->whereKey($membership->id)->exists();
            $membership->fill($this->memberData($request));
            $isAdmin = $membership->role === 'admin' && $membership->status === 'active'
                && $membership->joined_at->lte(now()) && (! $membership->ended_at || $membership->ended_at->gt(now()));
            if ($wasAdmin && ! $isAdmin && ! $organization->memberships()->active()->where('role', 'admin')->where('id', '!=', $membership->id)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['status' => 'Tetapkan admin aktif pengganti sebelum menonaktifkan admin terakhir.']);
            }
            $membership->save();
        });
        return redirect()->route('my.organizations')->with('status', 'Keanggotaan berhasil diperbarui.');
    }
}
