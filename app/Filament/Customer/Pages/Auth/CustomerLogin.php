<?php

namespace App\Filament\Customer\Pages\Auth;

use App\Auth\CustomerGuard;
use App\Exceptions\OtpRateLimitedException;
use App\Services\OtpService;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Filament\Forms\Components\OneTimeCodeInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

/**
 * @property-read Schema $form
 */
class CustomerLogin extends SimplePage
{
    use RestrictsFileUploadsToSchemaComponents;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    #[Locked]
    public string $step = 'mobile';

    #[Locked]
    public ?string $mobile = null;

    #[Locked]
    public ?int $otpExpiresAt = null;

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill();
    }

    public function requestCode(): void
    {
        $data = $this->form->getState();
        $mobile = $data['mobile'];

        try {
            $otp = app(OtpService::class)->send($mobile);
        } catch (OtpRateLimitedException $exception) {
            $this->notifyRateLimited($exception);

            return;
        }

        $this->startCodeStep($mobile, $otp->expires_at?->getTimestamp());

        Notification::make()
            ->title('کد تأیید ارسال شد')
            ->body('کد ۶ رقمی تا ۲ دقیقه معتبر است.')
            ->success()
            ->send();
    }

    public function authenticate(): ?LoginResponse
    {
        if ($this->step !== 'code' || blank($this->mobile)) {
            $this->resetToMobileStep();

            return null;
        }

        $otp = app(OtpService::class);

        if ($otp->tooManyVerifyAttempts($this->mobile)) {
            $this->notifyRateLimited(new OtpRateLimitedException($otp->verifyRetryAvailableIn($this->mobile)));

            return null;
        }

        $data = $this->form->getState();

        try {
            $authenticated = $this->customerGuard()->attemptWithOtp($this->mobile, (string) $data['code']);
        } catch (OtpRateLimitedException $exception) {
            $this->notifyRateLimited($exception);

            return null;
        }

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'data.code' => 'کد واردشده نامعتبر یا منقضی است.',
            ]);
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    public function resendCode(): void
    {
        if (blank($this->mobile)) {
            $this->resetToMobileStep();

            return;
        }

        try {
            $otp = app(OtpService::class)->send($this->mobile);
        } catch (OtpRateLimitedException $exception) {
            $this->notifyRateLimited($exception);

            return;
        }

        $this->otpExpiresAt = $otp->expires_at?->getTimestamp();
        $this->form->fill(['code' => null]);

        Notification::make()
            ->title('کد جدید ارسال شد')
            ->body('کد ۶ رقمی تا ۲ دقیقه معتبر است.')
            ->success()
            ->send();
    }

    public function resetToMobileStep(): void
    {
        $previousMobile = $this->mobile;

        $this->step = 'mobile';
        $this->mobile = null;
        $this->otpExpiresAt = null;
        $this->form->fill([
            'mobile' => $previousMobile,
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getMobileFormComponent(),
                $this->getCodeFormComponent(),
            ]);
    }

    protected function getMobileFormComponent(): Component
    {
        return TextInput::make('mobile')
            ->label('شماره موبایل')
            ->placeholder('09121234567')
            ->tel()
            ->prefixIcon(Heroicon::OutlinedPhone)
            ->helperText('شماره‌ای که هنگام ثبت‌نام در سیستم وارد شده است.')
            ->required()
            ->autocomplete('tel')
            ->autofocus()
            ->maxLength(11)
            ->regex('/^09\d{9}$/')
            ->validationMessages([
                'regex' => 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.',
            ])
            ->visible(fn (): bool => $this->step === 'mobile');
    }

    protected function getCodeFormComponent(): Component
    {
        return OneTimeCodeInput::make('code')
            ->label('کد تأیید')
            ->length(OtpService::CODE_LENGTH)
            ->required()
            ->autofocus()
            ->visible(fn (): bool => $this->step === 'code');
    }

    public function getMaxWidth(): Width|string|null
    {
        return Width::Large;
    }

    public function getTitle(): string|Htmlable
    {
        return 'ورود مشتریان';
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->step === 'code' ? 'وارد کردن کد تأیید' : 'ورود با شماره موبایل';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if ($this->step === 'code') {
            return 'کد ۶ رقمی پیامک‌شده را وارد کنید.';
        }

        return 'برای ورود، شماره موبایل ثبت‌شده در سیستم را وارد کنید.';
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        if ($this->step === 'code') {
            return [
                $this->getAuthenticateFormAction(),
            ];
        }

        return [
            $this->getRequestCodeAction(),
        ];
    }

    protected function getRequestCodeAction(): Action
    {
        return Action::make('requestCode')
            ->label('دریافت کد تأیید')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->submit('requestCode');
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('ورود به پنل')
            ->icon(Heroicon::OutlinedArrowLeftEndOnRectangle)
            ->submit('authenticate');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    protected function getOtpHintComponent(): Component
    {
        return Html::make(fn (): HtmlString => new HtmlString(view('filament.customer.auth.otp-hint', [
            'mobile' => $this->mobile,
            'expiresAt' => $this->otpExpiresAt,
        ])->render()))
            ->visible(fn (): bool => $this->step === 'code' && filled($this->mobile));
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler($this->step === 'code' ? 'authenticate' : 'requestCode')
            ->footer([
                $this->getOtpHintComponent(),
                Actions::make($this->getFormActions())
                    ->alignment(Alignment::Center)
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),
                $this->getSecondaryActionsComponent(),
            ]);
    }

    protected function getSecondaryActionsComponent(): Component
    {
        return Actions::make([
            Action::make('changeMobile')
                ->label('شماره را اشتباه زدید؟ تغییر شماره')
                ->link()
                ->color('primary')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->iconPosition(IconPosition::Before)
                ->action('resetToMobileStep'),
        ])
            ->alignment(Alignment::Center)
            ->visible(fn (): bool => $this->step === 'code');
    }

    private function startCodeStep(string $mobile, ?int $expiresAt): void
    {
        $this->mobile = $mobile;
        $this->step = 'code';
        $this->otpExpiresAt = $expiresAt ?? now()->addMinutes(OtpService::TTL_MINUTES)->getTimestamp();
        $this->form->fill(['code' => null]);
    }

    private function customerGuard(): CustomerGuard
    {
        $guard = Filament::auth();

        if (! $guard instanceof CustomerGuard) {
            throw new \RuntimeException('پنل مشتری باید از CustomerGuard استفاده کند.');
        }

        return $guard;
    }

    private function notifyRateLimited(OtpRateLimitedException $exception): void
    {
        Notification::make()
            ->title('تعداد تلاش‌ها بیش از حد مجاز است')
            ->body("لطفاً {$exception->secondsUntilAvailable} ثانیه دیگر دوباره تلاش کنید.")
            ->danger()
            ->send();
    }
}
