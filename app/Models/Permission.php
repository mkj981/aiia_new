<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    // 🔥 CRITICAL FIX
    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'guard_name',
    ];
}
