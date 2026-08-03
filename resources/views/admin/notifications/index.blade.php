@extends('layouts.admin', ['heading' => 'Notifications'])

@section('title', 'Notifications')

@section('content')
    <x-ui.page-header title="Notifications" :subtitle="$unreadCount.' unread'">
        <x-slot:actions>
            @if ($unreadCount > 0)
                <form action="{{ route('admin.notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-light">Mark All as Read</button>
                </form>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="card border-0 shadow-sm">
        <div class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isUnread = $notification->read_at === null;
                    $variant = match ($data['severity'] ?? 'info') {
                        'danger' => 'danger',
                        'warning' => 'warning',
                        default => 'primary',
                    };
                @endphp
                <div @class(['list-group-item py-4', 'bg-light' => $isUnread])>
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                @if ($isUnread)
                                    <span class="badge rounded-pill text-bg-{{ $variant }}">New</span>
                                @endif
                                <h2 class="h6 fw-bold mb-0">{{ $data['title'] ?? 'Notification' }}</h2>
                                @if (($data['count'] ?? 0) > 0)
                                    <span class="badge text-bg-light border">{{ $data['count'] }}</span>
                                @endif
                            </div>
                            <p class="mb-2 text-muted">{{ $data['message'] ?? '' }}</p>
                            <div class="small text-muted">{{ $notification->created_at->diffForHumans() }}</div>
                        </div>

                        <div class="d-flex gap-2">
                            @if ($isUnread)
                                <form action="{{ route('admin.notifications.read', $notification->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        Mark Read & View
                                    </button>
                                </form>
                            @elseif (! empty($data['action_url']))
                                <a href="{{ $data['action_url'] }}" class="btn btn-sm btn-light">View</a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="list-group-item py-5 text-center text-muted">
                    No notifications yet. Alerts will appear here when action is needed.
                </div>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
@endsection
