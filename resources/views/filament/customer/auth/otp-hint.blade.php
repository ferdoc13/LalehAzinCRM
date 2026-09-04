@php
    $expiresAt = (int) ($expiresAt ?? 0);
    $mobile = (string) ($mobile ?? '');
@endphp

<div
    wire:key="otp-hint-{{ $expiresAt }}"
    x-data="{
        expiresAt: {{ $expiresAt }},
        remaining: Math.max(0, {{ $expiresAt }} - Math.floor(Date.now() / 1000)),
        timer: null,
        init() {
            this.tick()
            this.timer = setInterval(() => this.tick(), 1000)
        },
        destroy() {
            if (this.timer) {
                clearInterval(this.timer)
            }
        },
        tick() {
            this.remaining = Math.max(0, this.expiresAt - Math.floor(Date.now() / 1000))

            if (this.remaining <= 0 && this.timer) {
                clearInterval(this.timer)
                this.timer = null
            }
        },
        get display() {
            const minutes = Math.floor(this.remaining / 60)
            const seconds = this.remaining % 60

            return minutes + ':' + String(seconds).padStart(2, '0')
        }
    }"
    class="otp-hint"
>
    <div class="otp-hint-row">
        <div class="otp-hint-icon-wrap" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
            </svg>
        </div>

        <div class="otp-hint-body">
            <p class="otp-hint-title">
                کد تأیید ارسال شد
            </p>
            <p class="otp-hint-text">
                پیامک حاوی کد ۶ رقمی به شماره
                <span class="otp-hint-mobile">{{ $mobile }}</span>
                ارسال شده است.
            </p>
        </div>
    </div>

    <div x-show="remaining > 0" class="otp-hint-timer">
        <div class="otp-hint-timer-label">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span>زمان باقی‌مانده</span>
        </div>

        <span class="otp-hint-countdown" x-text="display">2:00</span>
    </div>

    <div x-show="remaining <= 0" x-cloak style="display: none" class="otp-hint-expired">
        <p class="otp-hint-expired-text">
            اعتبار کد به پایان رسید.
        </p>

        <button
            type="button"
            wire:click="resendCode"
            wire:loading.attr="disabled"
            class="fi-link fi-size-sm fi-color fi-color-primary"
            style="margin-top: 0.5rem"
        >
            <span class="fi-link-label">ارسال دوباره کد</span>
        </button>
    </div>
</div>
