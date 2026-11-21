<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class University extends Model
{
    protected $fillable = ['name', 'code'];

    public function faculties(): HasMany
    {
        return $this->hasMany(Faculty::class);
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(UniversityMember::class);
    }
}
