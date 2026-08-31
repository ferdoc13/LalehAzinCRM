<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\StaffRole;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    private string $assignedRole = StaffRole::Employee->value;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->assignedRole = StaffRole::fromForm($data['role'] ?? null)->value;
        unset($data['role']);
        $data['email_verified_at'] = now();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->syncRoles([$this->assignedRole]);
    }
}
