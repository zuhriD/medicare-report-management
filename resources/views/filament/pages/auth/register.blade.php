<div>
    {{-- Google Fonts: Inter --}}
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        /* ─── Reset & Base ─────────────────────────────── */
        .auth-reg-wrapper,
        .auth-reg-wrapper * {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            box-sizing: border-box;
        }

        .auth-reg-wrapper {
            display: flex;
            max-height: 100vh;
            background: #ffffff;
        }

        /* ─── Left Panel ────────────────────────────────── */
        .auth-reg-left {
            display: none;
            width: 50%;
            padding: 1.5rem;
            flex-shrink: 0;
            align-self: stretch;
        }

        @media (min-width: 1024px) {
            .auth-reg-left {
                display: flex;
            }
        }

        .auth-reg-card {
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

        .auth-reg-orb-1 {
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

        .auth-reg-orb-2 {
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

        .auth-reg-card-top {
            position: relative;
            z-index: 10;
        }

        .auth-reg-icon-white {
            font-size: 2.5rem;
            font-weight: 800;
            color: #ffffff;
            line-height: 1;
        }

        .auth-reg-card-bottom {
            position: relative;
            z-index: 10;
        }

        .auth-reg-tagline {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.875rem;
            font-weight: 500;
            margin: 0 0 0.75rem 0;
        }

        .auth-reg-headline {
            color: #ffffff;
            font-size: 1.875rem;
            font-weight: 700;
            line-height: 1.2;
            margin: 0;
        }

        /* ─── Right Panel ────────────────────────────────── */
        .auth-reg-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 2rem;
        }

        @media (min-width: 1024px) {
            .auth-reg-right {
                padding: 3rem 4rem;
            }
        }

        @media (min-width: 1280px) {
            .auth-reg-right {
                padding: 3rem 6rem;
            }
        }

        .auth-reg-form-wrap {
            width: 100%;
            max-width: 28rem;
            margin: 0 auto;
        }

        .auth-reg-header {
            margin-bottom: 1.2rem;
        }

        .auth-reg-icon-indigo {
            font-size: 2.25rem;
            font-weight: 800;
            color: #c33838;
            line-height: 1;
        }

        .auth-reg-title {
            font-size: 1.875rem;
            font-weight: 800;
            color: #111827;
            margin: 0.5rem 0 0.5rem 0;
            letter-spacing: -0.02em;
        }

        .auth-reg-subtitle {
            font-size: 0.875rem;
            color: #6b7280;
            line-height: 1.6;
            margin: 0;
        }

        /* ─── Filament Form Overrides ───────────────────── */
        .auth-reg-form .fi-fo-field-wrp>label,
        .auth-reg-form .fi-fo-field-wrp label {
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            color: #374151 !important;
        }

        .auth-reg-form .fi-input {
            border-radius: 0.625rem !important;
            padding: 0.6rem 1rem !important;
            font-size: 0.9rem !important;
            background: #ffffff !important;
            transition: border-color 0.15s, box-shadow 0.15s !important;
        }

        .auth-reg-form .fi-input:focus {
            border-color: #335dc5 !important;
            /* box-shadow: 0 0 0 3px rgba(51, 93, 197, 0.12) !important; */
            outline: none !important;
        }

        .auth-reg-form .fi-btn-primary,
        .auth-reg-form [type="submit"] {
            background: linear-gradient(90deg, #335dc5, #517ce6) !important;
            border-radius: 0.625rem !important;
            font-weight: 600 !important;
            font-size: 1rem !important;
            padding: 0.875rem 1.5rem !important;
            letter-spacing: 0.01em !important;
            transition: all 0.2s !important;
            /* box-shadow: 0 4px 15px rgba(51, 93, 197, 0.2) !important; */
            border: none !important;
            color: #ffffff !important;
            cursor: pointer !important;
            width: 100% !important;
        }

        .auth-reg-form .fi-btn-primary:hover,
        .auth-reg-form [type="submit"]:hover {
            background: linear-gradient(90deg, #264bb3, #3a62cc) !important;
            box-shadow: 0 6px 20px rgba(51, 93, 197, 0.2) !important;
            transform: translateY(-1px) !important;
        }

        /* ─── Footer Text ───────────────────────────────── */
        .auth-reg-login-link {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .auth-reg-login-link a,
        .auth-reg-login-link span[wire\:click],
        .auth-reg-login-link .fi-link {
            font-weight: 600;
            color: #335dc5;
            text-decoration: none;
            transition: color 0.15s;
        }

        .auth-reg-login-link a:hover,
        .auth-reg-login-link .fi-link:hover {
            color: #264bb3;
        }

        .auth-reg-footer {
            margin-top: 1rem;
            text-align: center;
            font-size: 0.75rem;
            color: #9ca3af;
        }
    </style>

    <div class="auth-reg-wrapper">

        {{-- ── Left Gradient Card ─────────────────────────── --}}
        <div class="auth-reg-left">
            <div class="auth-reg-card">
                <div class="auth-reg-orb-1"></div>
                <div class="auth-reg-orb-2"></div>

                <div class="auth-reg-card-top">
                    <span class="auth-reg-icon-white">✳</span>
                </div>

                <div class="auth-reg-card-bottom">
                    <p class="auth-reg-tagline">Medicare Report Portal</p>
                    <h2 class="auth-reg-headline">
                        Manage your modules,<br>
                        weekly reports, and team<br>
                        progress in one place.
                    </h2>
                </div>
            </div>
        </div>

        {{-- ── Right Form Panel ──────────────────────────── --}}
        <div class="auth-reg-right">
            <div class="auth-reg-form-wrap">

                <div class="auth-reg-header">
                    <!-- <span class="auth-reg-icon-indigo">✳</span> -->
                    <h1 class="auth-reg-title">Create an account</h1>
                    <p class="auth-reg-subtitle">
                        Access your tasks, reports, and modules anytime,<br>
                        anywhere — and keep everything flowing in one place.
                    </p>
                </div>

                <div class="auth-reg-form">
                    <x-filament-panels::form wire:submit="register">
                        {{ $this->form }}
                        <x-filament-panels::form.actions
                            :actions="$this->getCachedFormActions()"
                            :full-width="$this->hasFullWidthFormActions()" />
                    </x-filament-panels::form>
                </div>

                @if (filament()->hasLogin())
                <p class="auth-reg-login-link">
                    Already have an account? {{ $this->loginAction }}
                </p>
                @endif

                <p class="auth-reg-footer">
                    &copy; {{ date('Y') }} Medicare Reports. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</div>