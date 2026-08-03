@props(['alerts'])

@if ($alerts->isNotEmpty())
    <div class="mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1">Alerts</h2>
                <p class="text-muted small mb-0">Important items that need your attention today.</p>
            </div>
            @if (($unreadNotificationsCount ?? 0) > 0)
                <a href="{{ route('admin.notifications.index') }}" class="btn btn-sm btn-light">
                    {{ $unreadNotificationsCount }} unread notification{{ $unreadNotificationsCount === 1 ? '' : 's' }}
                </a>
            @endif
        </div>

        <div class="row g-3">
            @foreach ($alerts as $alert)
                @php
                    $variant = match ($alert['severity']) {
                        'danger' => 'danger',
                        'warning' => 'warning',
                        default => 'info',
                    };
                @endphp
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100 sg-alert-card sg-alert-card-{{ $variant }}">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <x-ui.badge :variant="$variant">{{ $alert['count'] }}</x-ui.badge>
                                        <h3 class="h6 fw-bold mb-0">{{ $alert['title'] }}</h3>
                                    </div>
                                    <p class="text-muted small mb-0">{{ $alert['message'] }}</p>
                                </div>
                                <a href="{{ $alert['action_url'] }}" class="btn btn-sm btn-light text-nowrap">
                                    View
                                </a>
                            </div>

                            @if (! empty($alert['items']))
                                <ul class="list-unstyled mb-0 sg-alert-items">
                                    @foreach ($alert['items'] as $item)
                                        <li class="d-flex justify-content-between gap-3 py-2 border-top">
                                            <span class="fw-semibold">{{ $item['name'] }}</span>
                                            @if (! empty($item['detail']))
                                                <span class="text-muted small text-end">{{ $item['detail'] }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
