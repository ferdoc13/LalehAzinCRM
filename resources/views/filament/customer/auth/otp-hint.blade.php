@php
    $expiresAt = (int) ($expiresAt ?? 0);
    $mobile = (string) ($mobile ?? '');
@endphp

<style>[x-cloak]{display:none !important}</style>

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
    class="mb-4 space-y-3 text-center text-sm"
>
    <p class="text-gray-500 dark:text-gray-400">
        کد تأیید به شماره
        <span class="font-semibold text-gray-950 dark:text-white" dir="ltr">{{ $mobile }}</span>
        ارسال شد.
    </p>

    <p>
        <button
            type="button"
            wire:click="resetToMobileStep"
            class="text-primary-600 hover:text-primary-500 dark:text-primary-400 font-medium underline underline-offset-4"
        >
            شماره را اشتباه زدید؟ تغییر شماره
        </button>
    </p>

    <div
        x-show="remaining > 0"
        class="text-gray-950 dark:text-white"
    >
        <p class="text-xs text-gray-500 dark:text-gray-400">زمان باقی‌مانده پیامک</p>
        <p
            class="mt-1 font-mono text-2xl font-semibold tabular-nums tracking-wide"
            dir="ltr"
            x-text="display"
        >2:00</p>
    </div>

    <p
        x-show="remaining <= 0"
        x-cloak
        class="text-danger-600 dark:text-danger-400"
    >
        اعتبار کد به پایان رسید.
        <button
            type="button"
            wire:click="resendCode"
            class="text-primary-600 hover:text-primary-500 dark:text-primary-400 font-medium underline underline-offset-4"
        >
            ارسال دوباره کد
        </button>
    </p>
</div>
