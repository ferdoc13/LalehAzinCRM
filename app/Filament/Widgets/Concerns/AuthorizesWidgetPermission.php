<?php

namespace App\Filament\Widgets\Concerns;

use App\Models\User;

trait AuthorizesWidgetPermission
{
    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can(static::widgetPermission());
    }

    public function mountCanAuthorizeAccess(): void
    {
        abort_unless(static::canView(), 403);
    }

    public function hydrateCanAuthorizeAccess(): void
    {
        abort_unless(static::canView(), 403);
    }

    abstract protected static function widgetPermission(): string;
}
