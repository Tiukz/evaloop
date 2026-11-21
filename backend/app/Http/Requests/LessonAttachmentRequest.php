<?php

namespace App\Http\Requests;

use App\Models\LessonAttachment;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class LessonAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('lesson'));
    }

    public function rules(): array
    {
        return self::attachmentRules(required: true);
    }

    public static function attachmentRules(bool $required): array
    {
        return [
            'attachments' => [$required ? 'required' : 'sometimes', 'array', 'list', 'max:5'],
            'attachments.*' => [
                'bail', 'required', 'file', 'max:'.config('evaloop.lesson_attachment_max_kb'),
                function (string $attribute, UploadedFile $file, Closure $fail): void {
                    $extension = strtolower($file->getClientOriginalExtension());
                    $mime = LessonAttachment::MIME_TYPES[$extension] ?? null;

                    if (! $mime || ! self::matchesContent($file, $extension, $mime)) {
                        $fail('Isi file harus sesuai dengan format PDF, DOCX, JPG, JPEG, PNG, atau WEBP.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return self::attachmentMessages();
    }

    public static function attachmentMessages(): array
    {
        return [
            'attachments.required' => 'Pilih file yang ingin diunggah.',
            'attachments.max' => 'Satu evaluasi dapat memiliki maksimal 5 lampiran.',
            'attachments.*.uploaded' => 'File gagal diunggah. Periksa batas ukuran file dan coba lagi.',
            'attachments.*.max' => 'Ukuran setiap file maksimal :max KB.',
        ];
    }

    private static function matchesContent(UploadedFile $file, string $extension, string $mime): bool
    {
        $path = $file->getPathname();
        $detected = $file->getMimeType();

        if ($extension === 'docx') {
            return in_array($detected, [$mime, 'application/zip'], true) && self::isDocx($path);
        }

        if ($detected !== $mime) {
            return false;
        }

        return $extension === 'pdf'
            ? file_get_contents($path, false, null, 0, 5) === '%PDF-'
            : (@getimagesize($path)['mime'] ?? null) === $mime;
    }

    private static function isDocx(string $path): bool
    {
        $archive = new ZipArchive;
        if ($archive->open($path, ZipArchive::RDONLY) !== true) {
            return false;
        }

        try {
            // Inspect a bounded manifest without extracting or processing XML.
            $manifest = $archive->statName('[Content_Types].xml');
            if (! $manifest || $manifest['size'] > 262144 || $archive->locateName('word/document.xml') === false) {
                return false;
            }
            $types = $archive->getFromName('[Content_Types].xml', 262144);

            return $types !== false
                && str_contains($types, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml')
                && $archive->locateName('word/vbaProject.bin', ZipArchive::FL_NOCASE) === false;
        } finally {
            $archive->close();
        }
    }
}
