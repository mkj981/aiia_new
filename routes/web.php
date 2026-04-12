<?php

use App\Models\User;
use App\Models\Role as AppRole;
use Spatie\Permission\Models\Role;

Route::get('/test-role-2', function () {

    return \App\Models\Role::create([
        'name' => 'Payroll Manager ' . rand(1,1000),
        'guard_name' => 'web',
    ]);
});


Route::get('/debug-permissions', function () {
    $user = auth()->user();

    return [
        'roles' => $user->roles->pluck('name'),
        'permissions' => $user->getAllPermissions()->pluck('name'),
    ];
});
Route::get('/test-assign-role', function () {

    $user = \App\Models\User::first();

    $user->assignRoleScoped('Super Admin');

    return [
        'roles' => $user->getRoleNames()
    ];
});

Route::get('/test-role', function () {

    // create user
    $user = User::first() ?? User::create([
        'name' => 'Test Admin',
        'email' => 'admin@test.com',
        'password' => bcrypt('123456'),
        'employer_id' => null, // global admin
    ]);

    // create role (if not exists)
    $role = Role::firstOrCreate([
        'name' => 'Super Admin',
        'guard_name' => 'web',
        'employer_id' => null,
    ]);

    // assign role
    $user->assignRole($role);

    return [
        'user' => $user->email,
        'has_role' => $user->hasRole('Super Admin'),
    ];
});

Route::get('/test-scope', function () {
    return \App\Models\User::pluck('email');
});
