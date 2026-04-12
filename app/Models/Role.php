<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    // 🔥 CRITICAL FIX
    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'guard_name',
        'employer_id',
    ];

    protected static function booted()
    {
        static::creating(function ($role) {

            // Keep employer if set manually
            if (!is_null($role->employer_id)) {
                return;
            }

            if (auth()->check()) {
                $user = auth()->user();

                // System Admin → global role
                if ($user->isSystemAdmin()) {
                    $role->employer_id = null;
                } else {
                    // Employer Admin → scoped role
                    $role->employer_id = $user->employer_id;
                }
            }
        });
    }

    // 🔥 RELATION
    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    // 🔥 SCOPE
    public function scopeForEmployer(Builder $query, $employerId)
    {
        return $query->where('employer_id', $employerId);
    }

    // 🔥 HELPER
    public function isGlobal(): bool
    {
        return is_null($this->employer_id);
    }
}
