<aside class="sg-auth-banner">
    <div class="sg-auth-banner__media" aria-hidden="true">
        <img
            src="{{ asset('images/auth/bodybuilding-banner.jpg') }}"
            alt=""
            class="sg-auth-banner__image"
            loading="eager"
            decoding="async"
        >
    </div>

    <div class="sg-auth-banner__overlay" aria-hidden="true"></div>

    <div class="sg-auth-banner__content">
        <div class="sg-auth-banner__brand">
            <x-auth.logo />
            <div>
                <p class="sg-auth-banner__eyebrow">{{ __('auth.banner.staff_portal') }}</p>
                <h2 class="sg-auth-banner__title">{{ config('gym.defaults.name') }}</h2>
            </div>
        </div>

        <div class="sg-auth-banner__hero">
            <p class="sg-auth-banner__tagline">{{ __('auth.banner.tagline') }}</p>

            <ul class="sg-auth-banner__features">
                <li>
                    <span class="sg-auth-banner__feature-icon" aria-hidden="true">
                        <i class="bi bi-people-fill"></i>
                    </span>
                    <span>{{ __('auth.banner.feature_members') }}</span>
                </li>
                <li>
                    <span class="sg-auth-banner__feature-icon" aria-hidden="true">
                        <i class="bi bi-cash-stack"></i>
                    </span>
                    <span>{{ __('auth.banner.feature_payments') }}</span>
                </li>
                <li>
                    <span class="sg-auth-banner__feature-icon" aria-hidden="true">
                        <i class="bi bi-bar-chart-line"></i>
                    </span>
                    <span>{{ __('auth.banner.feature_reports') }}</span>
                </li>
            </ul>
        </div>

        <div class="sg-auth-banner__footer">
            <span class="sg-auth-banner__badge">
                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                {{ __('auth.banner.secure_access') }}
            </span>
        </div>
    </div>
</aside>
