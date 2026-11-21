<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizationRequest;
use App\Models\Organization;
use App\Models\University;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UniversityOrganizationController extends Controller
{
    public function index(University $university): View
    {
        Gate::authorize('manage', $university);
        $organizations = $university->organizations()->with(['faculty', 'studyProgram'])->orderBy('name')->paginate(12);

        return view('organizations.index', compact('university', 'organizations'));
    }

    public function create(University $university): View
    {
        Gate::authorize('manage', $university);

        return view('organizations.form', ['university' => $university, 'organization' => new Organization,
            'faculties' => $university->faculties()->with('studyPrograms')->get()]);
    }

    public function edit(University $university, Organization $organization): View
    {
        Gate::authorize('administer', $organization);

        return view('organizations.form', ['university' => $university, 'organization' => $organization,
            'faculties' => $university->faculties()->with('studyPrograms')->get()]);
    }

    public function store(OrganizationRequest $request, University $university): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $university) {
            $university = University::lockForUpdate()->findOrFail($university->id);
            Gate::authorize('manage', $university);
            $organization = $university->organizations()->create($request->safe()->only(['name', 'faculty_id', 'study_program_id']));
            $organization->memberships()->create(['user_id' => $request->validated('initial_admin_user_id'),
                'role' => 'admin', 'status' => 'active', 'joined_at' => now()]);
        });
        return redirect()->route('university.organizations.index', $university)->with('status', 'Organisasi dan admin pertama berhasil dibuat.');
    }

    public function update(OrganizationRequest $request, University $university, Organization $organization): \Illuminate\Http\RedirectResponse
    {
        $organization->update($request->safe()->only(['name', 'faculty_id', 'study_program_id']));
        return redirect()->route('university.organizations.index', $university)->with('status', 'Organisasi berhasil diperbarui.');
    }
}
