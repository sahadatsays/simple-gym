@props([
    'groups',
])

<nav class="sg-sidebar-nav flex-column py-3" aria-label="Admin navigation">
    @foreach ($groups as $group)
        @if ($group['single'])
            @php($item = $group['items']->first())
            <a
                href="{{ route($item['route']) }}"
                @class(['sg-sidebar-link', 'active' => $item['active']])
                title="{{ $item['label'] }}"
            >
                <span class="sg-sidebar-link-content">
                    <i class="bi bi-{{ $item['icon'] }} sg-sidebar-link-icon" aria-hidden="true"></i>
                    <span class="sg-sidebar-link-label">{{ $item['label'] }}</span>
                </span>
            </a>
        @else
            <div
                class="sg-sidebar-group"
                x-data="{ open: @js($group['active']) }"
            >
                <button
                    type="button"
                    class="sg-sidebar-group-toggle"
                    :class="{ 'active': @js($group['active']), 'is-open': open }"
                    @click="open = !open"
                    :aria-expanded="open"
                >
                    <span class="sg-sidebar-link-content">
                        <i class="bi bi-{{ $group['icon'] }} sg-sidebar-link-icon" aria-hidden="true"></i>
                        <span class="sg-sidebar-link-label">{{ $group['label'] }}</span>
                    </span>
                    <i class="bi bi-chevron-down sg-sidebar-group-chevron" aria-hidden="true"></i>
                </button>

                <div class="sg-sidebar-submenu" x-show="open" x-transition.opacity.duration.200ms>
                    @foreach ($group['items'] as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            @class(['sg-sidebar-sublink', 'active' => $item['active']])
                            @click="sidebarOpen = false"
                        >
                            <i class="bi bi-{{ $item['icon'] }} sg-sidebar-sublink-icon" aria-hidden="true"></i>
                            <span class="sg-sidebar-link-label">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
</nav>
