<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    protected $attributes = ['is_platform_admin' => false];

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_platform_admin' => 'boolean'];
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function universityMemberships(): HasMany
    {
        return $this->hasMany(UniversityMember::class);
    }

    public function organizationMemberships(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function eventMemberships(): HasMany
    {
        return $this->hasMany(EventMember::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_members')->withPivot('role')->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assignee_user_id');
    }

    public function isOrganizationAdmin(int $organizationId): bool
    {
        return $this->organizationMemberships()->active()
            ->where('organization_id', $organizationId)->where('role', 'admin')->exists();
    }

    public function isUniversityAdmin(int $universityId): bool
    {
        return $this->universityMemberships()->active()
            ->where('university_id', $universityId)->where('role', 'admin')->exists();
    }
}
