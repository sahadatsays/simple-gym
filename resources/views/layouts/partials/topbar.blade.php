<header class="sg-topbar d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2 gap-md-3">
        <button
            type="button"
            class="btn btn-light sg-topbar-icon-btn d-lg-none"
            @click="sidebarOpen = !sidebarOpen"
            aria-label="Open menu"
            aria-controls="adminSidebar"
        >
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        <button
            type="button"
            class="btn btn-light sg-topbar-icon-btn d-none d-lg-inline-flex"
            @click="sidebarCollapsed = !sidebarCollapsed"
            aria-label="Toggle sidebar"
        >
            <i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i>
        </button>

        @isset($heading)
            <h1 class="h5 mb-0 fw-semibold text-truncate">{{ $heading }}</h1>
        @endisset
    </div>

    <div class="d-flex align-items-center gap-2">
        @can('dashboard.view')
            <div class="dropdown">
                <button
                    class="btn btn-light position-relative sg-notification-button"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="Notifications"
                >
                    <i class="bi bi-bell" aria-hidden="true"></i>
                    @if (($unreadNotificationsCount ?? 0) > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger sg-notification-badge">
                            {{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}
                        </span>
                    @endif
                </button>
                <div class="dropdown-menu dropdown-menu-end sg-notification-menu p-0">
                    <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Notifications</span>
                        @if (($unreadNotificationsCount ?? 0) > 0)
                            <span class="badge text-bg-danger">{{ $unreadNotificationsCount }}</span>
                        @endif
                    </div>

                    @forelse ($recentNotifications ?? [] as $notification)
                        @php($data = $notification->data)
                        <form action="{{ route('admin.notifications.read', $notification->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item py-3 sg-notification-item">
                                <div class="fw-semibold mb-1">{{ $data['title'] ?? 'Alert' }}</div>
                                <div class="small text-muted">{{ $data['message'] ?? '' }}</div>
                                <div class="small text-muted mt-1">{{ $notification->created_at->diffForHumans() }}</div>
                            </button>
                        </form>
                    @empty
                        <div class="px-3 py-4 text-muted small text-center">
                            You're all caught up.
                        </div>
                    @endforelse

                    <div class="border-top">
                        <a href="{{ route('admin.notifications.index') }}" class="dropdown-item py-2 text-center fw-semibold">
                            View all notifications
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        <div class="dropdown">
            <button
                class="btn btn-light dropdown-toggle"
                type="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
            >
                {{ auth()->user()->name }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text text-muted small">{{ auth()->user()->email }}</span></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>
                <li><a class="dropdown-item" href="{{ route('profile.password.edit') }}">Change Password</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item">Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>

@once
    @push('styles')
        <style>
            .sg-notification-button {
                width: 2.5rem;
                height: 2.5rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0;
            }

            .sg-notification-badge {
                font-size: 0.65rem;
                min-width: 1.1rem;
            }

            .sg-notification-menu {
                width: min(22rem, 90vw);
            }

            .sg-notification-item {
                white-space: normal;
            }
        </style>
    @endpush
@endonce
