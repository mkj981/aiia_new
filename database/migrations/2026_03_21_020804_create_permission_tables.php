<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        throw_if(empty($tableNames), 'Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        throw_if($teams && empty($columnNames['team_foreign_key'] ?? null), 'Error: team_foreign_key not loaded.');

        /**
         * PERMISSIONS TABLE
         */
        Schema::create($tableNames['permissions'], static function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');

            // 🔥 Multi-tenant support
            $table->unsignedBigInteger('employer_id')->nullable()->index();

            $table->timestamps();

            // 🔥 Allow same permission per employer
            $table->unique(['name', 'guard_name', 'employer_id']);
        });

        /**
         * ROLES TABLE
         */
        Schema::create($tableNames['roles'], static function (Blueprint $table) use ($teams, $columnNames) {
            $table->id();

            if ($teams || config('permission.testing')) {
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
            }

            $table->string('name');
            $table->string('guard_name');

            // 🔥 Multi-tenant support
            $table->unsignedBigInteger('employer_id')->nullable()->index();

            $table->timestamps();

            // 🔥 Allow same role per employer
            if ($teams || config('permission.testing')) {
                $table->unique([
                    $columnNames['team_foreign_key'],
                    'name',
                    'guard_name',
                    'employer_id'
                ]);
            } else {
                $table->unique(['name', 'guard_name', 'employer_id']);
            }
        });

        /**
         * MODEL HAS PERMISSIONS
         */
        Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
            $table->unsignedBigInteger($pivotPermission);

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type']);

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->cascadeOnDelete();

            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key']);

                $table->primary([
                    $columnNames['team_foreign_key'],
                    $pivotPermission,
                    $columnNames['model_morph_key'],
                    'model_type'
                ]);
            } else {
                $table->primary([
                    $pivotPermission,
                    $columnNames['model_morph_key'],
                    'model_type'
                ]);
            }
        });

        /**
         * MODEL HAS ROLES
         */
        Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
            $table->unsignedBigInteger($pivotRole);

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type']);

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->cascadeOnDelete();

            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key']);

                $table->primary([
                    $columnNames['team_foreign_key'],
                    $pivotRole,
                    $columnNames['model_morph_key'],
                    'model_type'
                ]);
            } else {
                $table->primary([
                    $pivotRole,
                    $columnNames['model_morph_key'],
                    'model_type'
                ]);
            }
        });

        /**
         * ROLE HAS PERMISSIONS
         */
        Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->cascadeOnDelete();

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->cascadeOnDelete();

            $table->primary([$pivotPermission, $pivotRole]);
        });

        app('cache')
            ->store(config('permission.cache.store') !== 'default'
                ? config('permission.cache.store')
                : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }
};
