<?php

namespace App\Filament\Concerns;

use App\Models\User;

trait AuthorizesManagers
{
    public static function canView(): bool
    {
        return static::userIsManager();
    }

    public function mountCanAuthorizeAccess(): void
    {
        abort_unless(static::canView(), 403);
    }

    public function hydrateCanAuthorizeAccess(): void
    {
        abort_unless(static::canView(), 403);
    }

    protected static function userIsManager(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isManager();
    }
}
