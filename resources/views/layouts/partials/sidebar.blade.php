<aside class="sg-sidebar" :class="{ 'show': sidebarOpen }">
    <div class="sg-sidebar-brand">
        {{ $gymName ?? config('gym.defaults.name') }}
    </div>

    <nav class="sg-sidebar-nav nav flex-column py-3">
        @foreach ($menuItems as $item)
            <a
                href="{{ route($item['route']) }}"
                @class(['nav-link', 'active' => request()->routeIs($item['match'])])
            >
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
</aside>
