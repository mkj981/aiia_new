<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Filament\Facades\Filament;
use App\Models\Role;

class GeneratePermissions extends Command
{
    protected $signature = 'permissions:generate';

    protected $description = 'Generate permissions for all Filament resources';

    public function handle()
    {
        $this->info('Generating permissions...');

        // 🔥 Get all Filament resources
        $resources = Filament::getResources();

        $actions = ['view', 'create', 'edit', 'delete'];

        foreach ($resources as $resource) {

            $model = $resource::getModel();

            $name = class_basename($model);

            // Convert to plural snake_case
            $resourceName = Str::plural(Str::snake($name));

            foreach ($actions as $action) {

                $permissionName = "{$resourceName}.{$action}";

                Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ]);

                $this->line("✔ {$permissionName}");
            }
        }
        $superAdmin = Role::where('name', 'Super Admin')->first();

        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }
        $this->info('✅ Permissions generated successfully!');
    }
}
