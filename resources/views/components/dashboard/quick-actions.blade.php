@php
    $actions = collect([
        [
            'label' => __('dashboard.quick_actions.register'),
            'route' => 'admin.members.register.create',
            'icon' => 'person-plus',
            'variant' => 'primary',
            'visible' => auth()->user()?->can('members.create'),
        ],
        [
            'label' => __('dashboard.quick_actions.rfid_card'),
            'route' => 'admin.rfid-cards.index',
            'icon' => 'credit-card-2-front',
            'variant' => 'purple',
            'visible' => auth()->user()?->can('rfid-cards.view'),
        ],
        [
            'label' => __('dashboard.quick_actions.pos'),
            'route' => 'admin.pos.index',
            'icon' => 'shop-window',
            'variant' => 'success',
            'visible' => auth()->user()?->can('payments.create'),
        ],
        [
            'label' => __('dashboard.quick_actions.orders'),
            'route' => 'admin.orders.index',
            'icon' => 'bag-check',
            'variant' => 'info',
            'visible' => auth()->user()?->can('payments.view'),
        ],
        [
            'label' => __('dashboard.quick_actions.payment'),
            'route' => 'admin.payments.create',
            'icon' => 'wallet2',
            'variant' => 'warning',
            'visible' => auth()->user()?->can('payments.create'),
        ],
        [
            'label' => __('dashboard.quick_actions.renew'),
            'route' => 'admin.members.renew.create',
            'icon' => 'arrow-repeat',
            'variant' => 'danger',
            'visible' => auth()->user()?->can('members.view'),
        ],
    ])->filter(fn (array $action): bool => (bool) $action['visible']);

    $variants = [
        'primary' => ['bg' => 'bg-primary-subtle', 'text' => 'text-primary'],
        'success' => ['bg' => 'bg-success-subtle', 'text' => 'text-success'],
        'danger' => ['bg' => 'bg-danger-subtle', 'text' => 'text-danger'],
        'warning' => ['bg' => 'bg-warning-subtle', 'text' => 'text-warning-emphasis'],
        'info' => ['bg' => 'bg-info-subtle', 'text' => 'text-info-emphasis'],
        'purple' => ['bg' => 'bg-light', 'text' => 'text-dark'],
    ];
@endphp

@if ($actions->isNotEmpty())
    <div class="sg-dashboard-quick-actions mb-4">
        <div class="sg-dashboard-quick-actions-grid">
            @foreach ($actions as $action)
                @php($palette = $variants[$action['variant']] ?? $variants['primary'])

                <a href="{{ route($action['route']) }}" class="sg-dashboard-quick-action">
                    <span class="sg-dashboard-quick-action-icon {{ $palette['bg'] }} {{ $palette['text'] }}">
                        <i class="bi bi-{{ $action['icon'] }}" aria-hidden="true"></i>
                    </span>
                    <span class="sg-dashboard-quick-action-label">{{ $action['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif
