<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    // 🔥 CRITICAL FIX
    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'password',
        'employer_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // 🔥 RELATION
    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    // 🔥 ROLE HELPERS

    public function isSuperAdmin(): bool
    {
        return $this->employer_id === null && $this->hasRole('Super Admin');
    }

    public function isSystemAdmin(): bool
    {
        return $this->employer_id === null;
    }

    public function isEmployerUser(): bool
    {
        return $this->employer_id !== null;
    }

    // 🔥 SAFE ROLE ASSIGNMENT (OPTIONAL)
    public function assignRoleScoped($roleName)
    {
        $query = \App\Models\Role::where('name', $roleName);

        if ($this->isSystemAdmin()) {
            $query->whereNull('employer_id');
        } else {
            $query->where('employer_id', $this->employer_id);
        }

        $role = $query->first();

        if (!$role) {
            throw new \Exception("Role not found for this employer scope.");
        }

        return $this->assignRole($role);
    }

    // 🔥 FILAMENT ACCESS
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return true;
    }
}
