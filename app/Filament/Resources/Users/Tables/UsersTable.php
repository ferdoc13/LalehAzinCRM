<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\StaffRole;
use App\Filament\Resources\Users\Actions\ToggleUserBlockActions;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('نام')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('ایمیل')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label('نقش')
                    ->badge()
                    ->formatStateUsing(function (mixed $state): ?string {
                        $role = $state instanceof StaffRole
                            ? $state
                            : StaffRole::tryFrom((string) $state);

                        return $role?->getLabel() ?? (is_string($state) ? $state : null);
                    })
                    ->color(function (mixed $state): string|array|null {
                        $role = $state instanceof StaffRole
                            ? $state
                            : StaffRole::tryFrom((string) $state);

                        return $role?->getColor();
                    }),
                TextColumn::make('blocked_status')
                    ->label('وضعیت')
                    ->badge()
                    ->state(fn (User $record): string => $record->isBlocked() ? 'مسدود' : 'فعال')
                    ->color(fn (string $state): string => $state === 'مسدود' ? 'danger' : 'success'),
                TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('نقش')
                    ->options(StaffRole::class)
                    ->query(function (Builder $query, array $data): Builder {
                        $role = $data['value'] ?? null;

                        return filled($role) ? $query->role($role) : $query;
                    }),
                SelectFilter::make('blocked')
                    ->label('وضعیت')
                    ->options([
                        'active' => 'فعال',
                        'blocked' => 'مسدود',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'active' => $query->whereNull('blocked_at'),
                            'blocked' => $query->whereNotNull('blocked_at'),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                EditAction::make()->label('ویرایش'),
                ...ToggleUserBlockActions::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
