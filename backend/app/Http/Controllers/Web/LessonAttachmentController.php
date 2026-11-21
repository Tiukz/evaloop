<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonAttachmentRequest;
use App\Models\Event;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Support\LessonAttachmentUpload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LessonAttachmentController extends Controller
{
    public function store(LessonAttachmentRequest $request, LessonAttachmentUpload $upload, Event $event, Lesson $lesson)
    {
        $upload->save(function () use ($lesson) {
            $lesson = Lesson::lockForUpdate()->findOrFail($lesson->id);
            Gate::authorize('update', $lesson);

            return $lesson;
        }, $request->validated('attachments'), $request->user()->id);

        return $this->editRedirect($event, $lesson)->with('status', 'Lampiran berhasil diunggah.');
    }

    public function show(Event $event, Lesson $sourceLesson, int $attachment, string $disposition)
    {
        Gate::authorize('viewForEvent', [$sourceLesson, $event]);
        $file = $sourceLesson->attachments()->findOrFail($attachment);
        $preview = $disposition === 'preview';
        abort_if($preview && ! $file->canPreview(), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($file->path), 404);
        $headers = [
            'Content-Type' => $file->mime_type,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'none'; sandbox",
        ];

        // Generate a safe disposition that retains the original Unicode display name.
        $response = new BinaryFileResponse($disk->path($file->path), 200, $headers, public: false);
        $fallback = str_replace(['%', '/', '\\'], '_', Str::ascii($file->original_name));
        $response->setContentDisposition($preview ? 'inline' : 'attachment', $file->original_name, $fallback ?: basename($file->path));

        return $response;
    }

    public function destroy(Event $event, Lesson $lesson, LessonAttachment $attachment)
    {
        DB::transaction(function () use ($lesson, $attachment) {
            $lesson = Lesson::lockForUpdate()->findOrFail($lesson->id);
            Gate::authorize('update', $lesson);
            $lesson->attachments()->findOrFail($attachment->id)->delete();
        });

        // The route becomes inaccessible before the blob is removed.
        if (! Storage::disk('local')->delete($attachment->path)) {
            report(new RuntimeException('Failed to remove a private evaluation attachment after deleting its record.'));
            return $this->editRedirect($event, $lesson)->withErrors([
                'attachments' => 'Lampiran sudah tidak dapat diakses, tetapi file gagal dihapus dari penyimpanan.',
            ]);
        }

        return $this->editRedirect($event, $lesson)->with('status', 'Lampiran dihapus.');
    }

    private function editRedirect(Event $event, Lesson $lesson)
    {
        return redirect()->to(route('evaluations.edit', [$event, $lesson]).'#lesson-attachments');
    }
}
