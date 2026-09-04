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
    class="w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:border-white/10 dark:bg-gray-900 dark:ring-white/10"
>
    <div class="flex items-start gap-2.5">
        <div class="fi-color fi-color-primary flex size-8 shrink-0 items-center justify-center rounded-full bg-gray-100 dark:bg-white/5">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="fi-color fi-color-primary size-4 text-[rgb(var(--primary-600))] dark:text-[rgb(var(--primary-400))]">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
            </svg>
        </div>

        <div class="min-w-0 flex-1 space-y-1 text-start">
            <p class="text-sm font-semibold text-gray-950 dark:text-white">
                کد تأیید ارسال شد
            </p>
            <p class="text-sm leading-6 text-gray-500 dark:text-gray-400">
                پیامک حاوی کد ۶ رقمی به شماره
                <span class="font-semibold text-gray-950 dark:text-white" dir="ltr">{{ $mobile }}</span>
                ارسال شده است.
            </p>
        </div>
    </div>

    <div
        x-show="remaining > 0"
        class="fi-color fi-color-primary mt-4 flex items-center justify-between gap-3 rounded-lg px-4 py-3 ring-1 ring-inset ring-[color:var(--color-200)] bg-[color:var(--color-50)] dark:ring-[color:var(--color-800)] dark:bg-[color:var(--color-950)]/30"
    >
        <div class="flex items-center gap-2 text-sm font-medium text-[rgb(var(--primary-700))] dark:text-[rgb(var(--primary-300))]">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5 shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span>زمان باقی‌مانده</span>
        </div>

        <span
            class="font-mono text-lg font-bold tabular-nums tracking-wider text-[rgb(var(--primary-700))] dark:text-[rgb(var(--primary-300))]"
            dir="ltr"
            x-text="display"
        >2:00</span>
    </div>

    <div
        x-show="remaining <= 0"
        x-cloak
        style="display: none"
        class="fi-color fi-color-danger mt-4 rounded-lg px-4 py-3 ring-1 ring-inset ring-[color:var(--color-200)] bg-[color:var(--color-50)] dark:ring-[color:var(--color-800)] dark:bg-[color:var(--color-950)]/30"
    >
        <p class="text-sm font-medium text-[rgb(var(--danger-700))] dark:text-[rgb(var(--danger-400))]">
            اعتبار کد به پایان رسید.
        </p>

        <button
            type="button"
            wire:click="resendCode"
            wire:loading.attr="disabled"
            class="fi-link fi-size-sm fi-color fi-color-primary mt-2"
        >
            <span class="fi-link-label">ارسال دوباره کد</span>
        </button>
    </div>
</div>
