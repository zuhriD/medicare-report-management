<div>
    {{-- Google Fonts: Inter --}}
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        /* ─── Reset & Base ─────────────────────────────── */
        .auth-login-wrapper,
        .auth-login-wrapper * {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            box-sizing: border-box;
        }

        .auth-login-wrapper {
            display: flex;
            min-height: 100vh;
            background: #ffffff;
        }

        /* ─── Left Panel ────────────────────────────────── */
        .auth-login-left {
            display: none;
            width: 50%;
            padding: 1.5rem;
            flex-shrink: 0;
            align-self: stretch;
        }

        @media (min-width: 1024px) {
            .auth-login-left {
                display: flex;
            }
        }

        .auth-login-card {
            position: relative;
            width: 100%;
            min-height: calc(100vh - 3rem);
            border-radius: 1.5rem;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 2.5rem;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 35%, #fca5a5 70%, #c33838 100%);
            background-size: 200% 200%;
            animation: authGradientShift 8s ease infinite;
        }

        @keyframes authGradientShift {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .auth-login-orb-1 {
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.35) 0%, transparent 70%);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -60%);
            animation: authOrbFloat 6s ease-in-out infinite;
        }

        .auth-login-orb-2 {
            position: absolute;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(251, 207, 232, 0.5) 0%, transparent 70%);
            border-radius: 50%;
            bottom: 15%;
            left: 10%;
            animation: authOrbFloat 8s ease-in-out infinite reverse;
        }

        @keyframes authOrbFloat {

            0%,
            100% {
                transform: translateY(0) scale(1);
            }

            50% {
                transform: translateY(-20px) scale(1.05);
            }
        }

        .auth-login-card-top {
            position: relative;
            z-index: 10;
        }

        .auth-login-icon-white {
            font-size: 2.5rem;
            font-weight: 800;
            color: #ffffff;
            line-height: 1;
        }

        .auth-login-card-bottom {
            position: relative;
            z-index: 10;
        }

        .auth-login-tagline {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.875rem;
            font-weight: 500;
            margin: 0 0 0.75rem 0;
        }

        .auth-login-headline {
            color: #ffffff;
            font-size: 1.875rem;
            font-weight: 700;
            line-height: 1.2;
            margin: 0;
        }

        /* ─── Right Panel ────────────────────────────────── */
        .auth-login-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 2rem;
        }

        @media (min-width: 1024px) {
            .auth-login-right {
                padding: 3rem 4rem;
            }
        }

        @media (min-width: 1280px) {
            .auth-login-right {
                padding: 3rem 6rem;
            }
        }

        .auth-login-form-wrap {
            width: 100%;
            max-width: 28rem;
            margin: 0 auto;
        }

        .auth-login-header {
            margin-bottom: 2rem;
        }

        .auth-login-icon-indigo {
            font-size: 2.25rem;
            font-weight: 800;
            color: #c33838;
            line-height: 1;
        }

        .auth-login-title {
            font-size: 1.875rem;
            font-weight: 800;
            color: #111827;
            margin: 0.5rem 0 0.5rem 0;
            letter-spacing: -0.02em;
        }

        .auth-login-subtitle {
            font-size: 0.875rem;
            color: #6b7280;
            line-height: 1.6;
            margin: 0;
        }

        /* ─── Filament Form Overrides ───────────────────── */
        .auth-login-form .fi-fo-field-wrp>label,
        .auth-login-form .fi-fo-field-wrp label {
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            color: #374151 !important;
        }

        .auth-login-form .fi-input-wrp {
            box-shadow: inset 0 0 0 1px #d1d5db !important;
            background: #ffffff !important;
            border-radius: 0.5rem !important;
            transition: all 0.15s !important;
        }

        .auth-login-form .fi-input-wrp:focus-within {
            box-shadow: inset 0 0 0 2px #335dc5 !important;
        }

        .auth-login-form .fi-input {
            padding: 0.6rem 1rem !important;
            font-size: 0.9rem !important;
            background: transparent !important;
            color: #111827 !important;
        }

        .auth-login-form .fi-input::placeholder {
            color: #9ca3af !important;
        }

        .auth-login-form .fi-checkbox-input {
            background-color: #ffffff !important;
            border-color: #d1d5db !important;
        }

        .auth-login-form .fi-checkbox-input:checked {
            background-color: #335dc5 !important;
            border-color: #335dc5 !important;
        }

        .auth-login-form .fi-btn-primary,
        .auth-login-form [type="submit"] {
            background: linear-gradient(90deg, #335dc5, #517ce6) !important;
            border-radius: 0.625rem !important;
            font-weight: 600 !important;
            font-size: 1rem !important;
            padding: 0.875rem 1.5rem !important;
            letter-spacing: 0.01em !important;
            transition: all 0.2s !important;
            border: none !important;
            color: #ffffff !important;
            cursor: pointer !important;
            width: 100% !important;
        }

        .auth-login-form .fi-btn-primary:hover,
        .auth-login-form [type="submit"]:hover {
            background: linear-gradient(90deg, #264bb3, #3a62cc) !important;
            transform: translateY(-1px) !important;
        }

        /* ─── Footer Text ───────────────────────────────── */
        .auth-login-register-link {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .auth-login-register-link a,
        .auth-login-register-link span[wire\:click],
        .auth-login-register-link .fi-link {
            font-weight: 600;
            color: #335dc5;
            text-decoration: none;
            transition: color 0.15s;
        }

        .auth-login-register-link a:hover,
        .auth-login-register-link .fi-link:hover {
            color: #264bb3;
        }

        .auth-login-footer {
            margin-top: 1rem;
            text-align: center;
            font-size: 0.75rem;
            color: #9ca3af;
        }

        /* ─── Dark Mode ─────────────────────────────────── */
        .dark .auth-login-wrapper {
            background: #09090b; /* Filament Gray 950 */
        }
        .dark .auth-login-title {
            color: #fafafa;
        }
        .dark .auth-login-subtitle {
            color: #a1a1aa; /* Filament Gray 400 */
        }
        .dark .auth-login-form .fi-fo-field-wrp>label,
        .dark .auth-login-form .fi-fo-field-wrp label {
            color: #d4d4d8 !important; /* Filament Gray 300 */
        }
        .dark .auth-login-form .fi-input-wrp {
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.1) !important;
            background: rgba(255, 255, 255, 0.05) !important;
        }
        .dark .auth-login-form .fi-input {
            color: #fafafa !important;
        }
        .dark .auth-login-form .fi-input::placeholder {
            color: #a1a1aa !important;
        }
        .dark .auth-login-form .fi-checkbox-input {
            background-color: rgba(255, 255, 255, 0.05) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        .dark .auth-login-form .fi-checkbox-input:checked {
            background-color: #335dc5 !important;
            border-color: #335dc5 !important;
        }
        .dark .auth-login-register-link {
            color: #a1a1aa;
        }
    </style>

    <div class="auth-login-wrapper" style="position: relative;">

        {{-- ── Theme Switcher ─────────────────────────────── --}}
        @if (filament()->hasDarkMode() && (! filament()->hasDarkModeForced()))
            <div x-data="{ close: () => {} }" style="position: absolute; top: 1.5rem; right: 1.5rem; z-index: 50; padding: 0.25rem; border-radius: 0.75rem; border: 1px solid rgba(156, 163, 175, 0.2);" class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-md">
                <x-filament-panels::theme-switcher />
            </div>
        @endif

        {{-- ── Left Gradient Card ─────────────────────────── --}}
        <div class="auth-login-left">
            <div class="auth-login-card">
                <div class="auth-login-orb-1"></div>
                <div class="auth-login-orb-2"></div>

                <div class="auth-login-card-top">
                    <span class="auth-login-icon-white">✳</span>
                </div>

                <div class="auth-login-card-bottom">
                    <p class="auth-login-tagline">Medicare Report Portal</p>
                    <h2 class="auth-login-headline">
                        Manage your modules,<br>
                        weekly reports, and team<br>
                        progress in one place.
                    </h2>
                </div>
            </div>
        </div>

        {{-- ── Right Form Panel ──────────────────────────── --}}
        <div class="auth-login-right">
            <div class="auth-login-form-wrap">

                <div class="auth-login-header">
                    <h1 class="auth-login-title">Sign in to your account</h1>
                    <p class="auth-login-subtitle">
                        Please enter your credentials to access the portal.
                    </p>
                </div>

                <div class="auth-login-form">
                    <x-filament-panels::form wire:submit="authenticate">
                        {{ $this->form }}
                        <x-filament-panels::form.actions
                            :actions="$this->getCachedFormActions()"
                            :full-width="$this->hasFullWidthFormActions()" />
                    </x-filament-panels::form>
                </div>

                @if (filament()->hasRegistration())
                <p class="auth-login-register-link">
                    Don't have an account? {{ $this->registerAction }}
                </p>
                @endif

                <p class="auth-login-footer">
                    &copy; {{ date('Y') }} Medicare Reports. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</div>