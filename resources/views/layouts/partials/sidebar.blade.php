<aside
    id="adminSidebar"
    class="sg-sidebar"
    :class="{
        'show': sidebarOpen,
        'sg-sidebar-collapsed': sidebarCollapsed
    }"
    tabindex="-1"
    aria-labelledby="adminSidebarLabel"
>
    <div class="sg-sidebar-inner">
        <div class="sg-sidebar-brand">
            <a href="{{ route('admin.dashboard') }}" class="sg-sidebar-brand-link text-decoration-none">
                @if ($gymLogoUrl ?? null)
                    <img src="{{ $gymLogoUrl }}" alt="{{ $gymName ?? config('gym.defaults.name') }}" class="sg-sidebar-brand-logo">
                @endif
                <span class="sg-sidebar-brand-text">{{ $gymName ?? config('gym.defaults.name') }}</span>
            </a>

            <button
                type="button"
                class="btn btn-link sg-sidebar-close d-lg-none"
                @click="sidebarOpen = false"
                aria-label="{{ __('common.aria.close_menu') }}"
            >
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>

        <x-admin.sidebar-nav :groups="$menuGroups" />

        <div class="sg-sidebar-footer d-none d-lg-flex">
            <button
                type="button"
                class="sg-sidebar-collapse-btn"
                @click="sidebarCollapsed = !sidebarCollapsed"
                :title="sidebarCollapsed ? @js(__('common.aria.expand_sidebar')) : @js(__('common.aria.collapse_sidebar'))"
            >
                <i class="bi" :class="sidebarCollapsed ? 'bi-chevron-double-right' : 'bi-chevron-double-left'" aria-hidden="true"></i>
                <span class="sg-sidebar-link-label">{{ __('common.sidebar.collapse') }}</span>
            </button>
        </div>
    </div>
</aside>
