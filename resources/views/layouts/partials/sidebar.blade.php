<aside class="sg-sidebar" :class="{ 'show': sidebarOpen }">
    <div class="sg-sidebar-brand">
        {{ $gymName ?? config('gym.defaults.name') }}
    </div>

    <nav class="sg-sidebar-nav nav flex-column py-3">
        @can('dashboard.view')
            <a
                href="{{ route('admin.dashboard') }}"
                @class(['nav-link', 'active' => request()->routeIs('admin.dashboard')])
            >
                Dashboard
            </a>
        @endcan

        @can('viewAny', App\Models\User::class)
            <a
                href="{{ route('admin.users.index') }}"
                @class(['nav-link', 'active' => request()->routeIs('admin.users.*')])
            >
                Users
            </a>
        @endcan

        @can('settings.view')
            <a
                href="{{ route('admin.settings.edit') }}"
                @class(['nav-link', 'active' => request()->routeIs('admin.settings.*')])
            >
                Gym Settings
            </a>
        @endcan
    </nav>
</aside>
