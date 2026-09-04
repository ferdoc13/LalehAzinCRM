<style>
    .customer-otp-field .fi-fo-field-content-col {
        width: 100%;
    }

    .customer-otp-field .fi-one-time-code-input-ctn,
    .fi-one-time-code-input-ctn.customer-otp-input {
        display: grid !important;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        width: 100% !important;
        gap: 0.5rem;
        direction: ltr;
    }

    .customer-otp-field .fi-one-time-code-input-digit,
    .fi-one-time-code-input-ctn.customer-otp-input .fi-one-time-code-input-digit {
        width: 100% !important;
        max-width: none !important;
        height: 2.75rem;
    }

    .otp-hint {
        width: 100%;
        border-radius: 0.75rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.05);
        padding: 1rem;
    }

    .otp-hint-row {
        display: flex;
        align-items: flex-start;
        gap: 0.625rem;
    }

    .otp-hint-icon-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        flex-shrink: 0;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.08);
        color: rgb(var(--primary-400));
    }

    .otp-hint-icon-wrap svg {
        width: 1rem;
        height: 1rem;
        display: block;
        flex-shrink: 0;
    }

    .otp-hint-body {
        min-width: 0;
        flex: 1;
    }

    .otp-hint-title {
        margin: 0;
        font-size: 0.875rem;
        font-weight: 600;
        color: #fff;
    }

    .otp-hint-text {
        margin: 0.25rem 0 0;
        font-size: 0.875rem;
        line-height: 1.5rem;
        color: rgba(255, 255, 255, 0.6);
    }

    .otp-hint-mobile {
        font-weight: 600;
        color: #fff;
        direction: ltr;
        unicode-bidi: isolate;
    }

    .otp-hint-timer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-top: 1rem;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        background: rgba(var(--primary-400), 0.08);
        border: 1px solid rgba(var(--primary-400), 0.2);
    }

    .otp-hint-timer-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: rgb(var(--primary-300));
    }

    .otp-hint-timer-label svg {
        width: 0.875rem;
        height: 0.875rem;
        display: block;
        flex-shrink: 0;
    }

    .otp-hint-countdown {
        font-family: ui-monospace, monospace;
        font-size: 1.125rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        color: rgb(var(--primary-300));
        direction: ltr;
    }

    .otp-hint-expired {
        margin-top: 1rem;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        background: rgba(var(--danger-400), 0.08);
        border: 1px solid rgba(var(--danger-400), 0.2);
    }

    .otp-hint-expired-text {
        margin: 0;
        font-size: 0.875rem;
        font-weight: 500;
        color: rgb(var(--danger-400));
    }
</style>
