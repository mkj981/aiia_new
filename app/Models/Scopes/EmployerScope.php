<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class EmployerScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();

        // 🔥 System admin → see everything
        if ($user->isSystemAdmin()) {
            return;
        }

        // 🔥 Employer user → restrict data
        if ($user->isEmployerUser()) {
            $builder->where($model->getTable() . '.employer_id', $user->employer_id);
        }
    }
}
