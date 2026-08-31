<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\StaffRole;
use App\Filament\Resources\Users\Actions\ToggleUserBlockActions;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    private string $assignedRole = StaffRole::Employee->value;

    protected function getHeaderActions(): array
    {
        return [
            ...ToggleUserBlockActions::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['role'] = $this->record->getRoleNames()->first() ?? StaffRole::Employee->value;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->assignedRole = StaffRole::fromForm($data['role'] ?? null)->value;
        unset($data['role']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->syncRoles([$this->assignedRole]);
    }
}
