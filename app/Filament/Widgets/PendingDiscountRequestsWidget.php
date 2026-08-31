<?php

namespace App\Filament\Widgets;

use App\Enums\DiscountRequestStatus;
use App\Filament\Concerns\AuthorizesManagers;
use App\Filament\Resources\DiscountRequests\Actions\ReviewDiscountRequestActions;
use App\Filament\Resources\DiscountRequests\DiscountRequestResource;
use App\Models\DiscountRequest;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PendingDiscountRequestsWidget extends TableWidget
{
    use AuthorizesManagers;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('درخواست‌های تخفیف در انتظار تأیید')
            ->description('دسترسی سریع برای بررسی درخواست‌های pending')
            ->query(
                fn (): Builder => DiscountRequest::query()
                    ->with(['customer', 'requester'])
                    ->where('status', DiscountRequestStatus::Pending)
                    ->latest(),
            )
            ->columns([
                TextColumn::make('customer.full_name')
                    ->label('نام مشتری'),
                TextColumn::make('requester.name')
                    ->label('ثبت‌کننده'),
                TextColumn::make('proposed_amount')
                    ->label('مبلغ پیشنهادی')
                    ->numeric(decimalPlaces: 0),
                TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->jalaliDateTime('Y/m/d H:i'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('مشاهده')
                    ->url(fn (DiscountRequest $record): string => DiscountRequestResource::getUrl('view', ['record' => $record])),
                ...ReviewDiscountRequestActions::make(),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('درخواست در انتظاری وجود ندارد');
    }
}
