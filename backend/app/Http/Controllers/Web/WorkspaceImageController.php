<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Organization;
use App\Support\WorkspaceImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorkspaceImageController extends Controller
{
    public function poster(Request $request, WorkspaceImage $images, int $id): BinaryFileResponse
    {
        $event = Event::published()->find($id);
        if (! $event) {
            $event = Event::findOrFail($id);
            abort_unless($request->user()?->can('viewWorkspace', $event), 404);
        }

        return $images->response($event->poster_path);
    }

    public function logo(Request $request, WorkspaceImage $images, Organization $organization): BinaryFileResponse
    {
        abort_unless($request->user()?->can('view', $organization) || $organization->events()->published()->exists(), 404);

        return $images->response($organization->logo_path);
    }

    public function updateLogo(Request $request, WorkspaceImage $images, Organization $organization): RedirectResponse
    {
        Gate::authorize('manage', $organization);
        $data = $request->validate(['logo' => ['required', ...WorkspaceImage::RULES]], [
            'logo.required' => 'Pilih gambar logo.',
            'logo.image' => 'Pilih gambar JPG, PNG, atau WebP.',
            'logo.mimes' => 'Pilih gambar JPG, PNG, atau WebP.',
            'logo.max' => 'Ukuran logo maksimal 2 MB.',
            'logo.dimensions' => 'Dimensi logo maksimal 4096 × 4096 piksel.',
        ]);

        $images->save($data['logo'], 'organization-logos', 'logo', function (string $path) use ($organization) {
            $organization = Organization::lockForUpdate()->findOrFail($organization->id);
            Gate::authorize('manage', $organization);
            $previous = $organization->logo_path;
            $organization->forceFill(['logo_path' => $path])->save();

            return [$organization, $previous];
        });

        return redirect()->route('organizations.overview', $organization)->with('status', 'Logo organisasi berhasil disimpan.');
    }
}
