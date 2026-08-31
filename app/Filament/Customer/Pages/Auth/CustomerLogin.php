<?php

namespace App\Filament\Customer\Pages\Auth;

use App\Auth\CustomerGuard;
use App\Exceptions\OtpRateLimitedException;
use App\Services\OtpService;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;
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
            app(OtpService::class)->send($mobile);
        } catch (OtpRateLimitedException $exception) {
            $this->notifyRateLimited($exception);

            return;
        }

        $this->mobile = $mobile;
        $this->step = 'code';
        $this->form->fill(['code' => null]);

        Notification::make()
            ->title('کد تأیید ارسال شد')
            ->body('کد ۶ رقمی تا ۲ دقیقه معتبر است. در این مرحله کد در لاگ سیستم ثبت می‌شود.')
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
            app(OtpService::class)->send($this->mobile);
        } catch (OtpRateLimitedException $exception) {
            $this->notifyRateLimited($exception);

            return;
        }

        Notification::make()
            ->title('کد جدید ارسال شد')
            ->success()
            ->send();
    }

    public function resetToMobileStep(): void
    {
        $this->step = 'mobile';
        $this->mobile = null;
        $this->form->fill();
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
        return TextInput::make('code')
            ->label('کد تأیید')
            ->placeholder('کد ۶ رقمی')
            ->numeric()
            ->required()
            ->autocomplete('one-time-code')
            ->autofocus()
            ->length(6)
            ->visible(fn (): bool => $this->step === 'code');
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
        if ($this->step === 'code' && filled($this->mobile)) {
            return "کد ارسال‌شده به {$this->mobile} را وارد کنید.";
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
                $this->getResendCodeAction(),
                $this->getChangeMobileAction(),
            ];
        }

        return [
            $this->getRequestCodeAction(),
        ];
    }

    protected function getRequestCodeAction(): Action
    {
        return Action::make('requestCode')
            ->label('دریافت کد')
            ->submit('requestCode');
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('ورود')
            ->submit('authenticate');
    }

    protected function getResendCodeAction(): Action
    {
        return Action::make('resendCode')
            ->label('ارسال دوباره کد')
            ->color('gray')
            ->action('resendCode');
    }

    protected function getChangeMobileAction(): Action
    {
        return Action::make('changeMobile')
            ->label('تغییر شماره موبایل')
            ->color('gray')
            ->link()
            ->action('resetToMobileStep');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE),
                $this->getFormContentComponent(),
                RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler($this->step === 'code' ? 'authenticate' : 'requestCode')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment(Alignment::Start)
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),
            ]);
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
