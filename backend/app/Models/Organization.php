<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = ['university_id', 'faculty_id', 'study_program_id', 'name'];

    protected $hidden = ['logo_path'];

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function eventSeries(): HasMany
    {
        return $this->hasMany(EventSeries::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
