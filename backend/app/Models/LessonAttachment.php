<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonAttachment extends Model
{
    public const MIME_TYPES = [
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    protected $fillable = ['uploaded_by', 'original_name', 'path', 'mime_type', 'size_bytes'];

    protected $hidden = ['path'];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function canPreview(): bool
    {
        return in_array($this->mime_type, [self::MIME_TYPES['pdf'], 'image/jpeg', 'image/png', 'image/webp'], true);
    }
}
