<header class="sg-topbar d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
        <button
            type="button"
            class="btn btn-outline-secondary btn-sm d-lg-none"
            @click="sidebarOpen = !sidebarOpen"
        >
            Menu
        </button>

        @isset($heading)
            <h1 class="h5 mb-0 fw-semibold">{{ $heading }}</h1>
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2M8 1.918l-.797.161A4 4 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4 4 0 0 0-3.203-3.92zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5 5 0 0 1 13 6c0 .88.32 4.2 1.22 6"/>
                    </svg>
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

            .sg-alert-card {
                border-left: 4px solid transparent;
            }

            .sg-alert-card-danger {
                border-left-color: #dc2626;
            }

            .sg-alert-card-warning {
                border-left-color: #d97706;
            }

            .sg-alert-card-info {
                border-left-color: #2563eb;
            }
        </style>
    @endpush
@endonce
