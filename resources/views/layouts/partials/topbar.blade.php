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
</header>
