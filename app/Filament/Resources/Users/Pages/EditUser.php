<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    // 🔥 Remove header actions (we use modal instead)
    protected function getHeaderActions(): array
    {
        return [];
    }

    // 🔥 Load roles into form (VERY IMPORTANT)
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['roles'] = $this->record
            ->roles
            ->pluck('id')
            ->toArray();

        return $data;
    }

    // 🔥 Save roles after update
    protected function afterSave(): void
    {
        $this->record->syncRoles($this->data['roles'] ?? []);
    }
}
