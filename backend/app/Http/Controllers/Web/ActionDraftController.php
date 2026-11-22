<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Lesson;
use App\Support\ActionDraft;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ActionDraftController extends Controller
{
    public function __invoke(Request $request, Event $event, Lesson $sourceLesson, ActionDraft $provider): JsonResponse
    {
        Gate::authorize('actOn', [$sourceLesson, $event]);
        if ($request->except('_token') !== []) {
            return response()->json(['message' => 'Permintaan draf tidak valid.'], 422);
        }

        if ($event->tasks()->where('source_lesson_id', $sourceLesson->id)->exists()) {
            return response()->json(['message' => 'Tindak lanjut sudah dibuat. Muat ulang halaman untuk membukanya.'], 409);
        }

        $draft = $provider->generate($sourceLesson, $event);
        if ($draft === null) {
            return response()->json(['message' => 'Draf AI belum tersedia. Coba lagi atau isi tugas secara manual.'], 503);
        }

        return response()->json(['draft' => $draft, 'requires_review' => true])
            ->header('Cache-Control', 'private, no-store');
    }
}
