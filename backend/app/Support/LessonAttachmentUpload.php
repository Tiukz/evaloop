<?php

namespace App\Support;

use App\Models\LessonAttachment;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class LessonAttachmentUpload
{
    /** Save the lesson and its files together, cleaning up files if the transaction fails. */
    public function save(Closure $resolveLesson, array $files, int $uploaderId): void
    {
        if ($files === []) {
            $resolveLesson();

            return;
        }

        $disk = Storage::disk('local');
        $written = [];

        try {
            DB::transaction(function () use ($resolveLesson, $files, $uploaderId, $disk, &$written) {
                $lesson = $resolveLesson();
                if ($lesson->attachments()->count() + count($files) > 5) {
                    throw ValidationException::withMessages(['attachments' => 'Satu evaluasi dapat memiliki maksimal 5 lampiran.']);
                }

                foreach ($files as $file) {
                    $extension = strtolower($file->getClientOriginalExtension());
                    $directory = 'legacy-attachments/'.$lesson->id;
                    $filename = Str::uuid().'.'.$extension;
                    $written[] = $directory.'/'.$filename;
                    if ($disk->putFileAs($directory, $file, $filename, 'private') === false) {
                        report(new RuntimeException('Failed to store a private evaluation attachment.'));
                        throw ValidationException::withMessages(['attachments' => 'Penyimpanan gagal. Coba unggah kembali.']);
                    }

                    $name = preg_replace('/[\p{C}\/\\\\]+/u', '_', mb_scrub($file->getClientOriginalName(), 'UTF-8'));
                    $lesson->attachments()->create([
                        'uploaded_by' => $uploaderId,
                        'original_name' => Str::substr(trim($name), 0, 255) ?: $filename,
                        'path' => $directory.'/'.$filename,
                        'mime_type' => LessonAttachment::MIME_TYPES[$extension],
                        'size_bytes' => $file->getSize(),
                    ]);
                }
            });
        } catch (Throwable $exception) {
            if ($written !== [] && ! $disk->delete($written)) {
                report(new RuntimeException('Failed to clean up a private evaluation upload.'));
            }
            throw $exception;
        }
    }
}
