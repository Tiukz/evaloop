<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faculty extends Model
{
    protected $fillable = ['university_id', 'name', 'code'];

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function studyPrograms(): HasMany
    {
        return $this->hasMany(StudyProgram::class);
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }
}
