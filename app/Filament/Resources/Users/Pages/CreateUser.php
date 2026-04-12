<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    // 🔥 Optional (can keep or remove)
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }

    // 🔥 CRITICAL: assign roles after creating user
    protected function afterCreate(): void
    {
        $this->record->syncRoles($this->data['roles'] ?? []);
    }


}
