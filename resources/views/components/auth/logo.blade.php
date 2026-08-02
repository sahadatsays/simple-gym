<div {{ $attributes->merge(['class' => 'sg-auth-logo']) }}>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none" aria-hidden="true">
        <rect width="64" height="64" rx="16" fill="url(#sg-logo-gradient)"/>
        <path d="M14 32h6l2-8h20l2 8h6l-3.5-12H17.5L14 32z" fill="#fff" opacity="0.95"/>
        <rect x="10" y="30" width="6" height="4" rx="1.5" fill="#fff"/>
        <rect x="48" y="30" width="6" height="4" rx="1.5" fill="#fff"/>
        <rect x="8" y="28" width="4" height="8" rx="1.5" fill="#fff" opacity="0.85"/>
        <rect x="52" y="28" width="4" height="8" rx="1.5" fill="#fff" opacity="0.85"/>
        <defs>
            <linearGradient id="sg-logo-gradient" x1="8" y1="8" x2="56" y2="56" gradientUnits="userSpaceOnUse">
                <stop stop-color="#2563eb"/>
                <stop offset="1" stop-color="#1d4ed8"/>
            </linearGradient>
        </defs>
    </svg>
</div>
