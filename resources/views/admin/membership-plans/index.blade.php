@extends('layouts.admin', ['heading' => 'Membership Plans'])

@section('title', 'Membership Plans')

@section('content')
    <x-ui.page-header title="Membership Plans" subtitle="Manage gym membership packages and pricing">
        <x-slot:actions>
            @can('create', App\Models\MembershipPlan::class)
                <a href="{{ route('admin.membership-plans.create') }}" class="btn btn-primary">
                    Add Plan
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.membership-plans.index') }}" method="GET" class="sg-filter-grid">
            <x-admin.filter-field label="Search" for="search">
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Plan name or description..."
                    class="form-control ps-2"
                >
            </x-admin.filter-field>

            <x-admin.filter-field label="Status" for="status">
                <select name="status" id="status" class="form-select">
                    <option value="">All statuses</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Actions" class="sg-filter-actions-field">
                <div class="sg-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.membership-plans.index') }}" class="btn btn-light">Reset</a>
                </div>
            </x-admin.filter-field>
        </form>
    </x-admin.filter-bar>

    <div class="card border-0 shadow-sm sg-data-table-card">
        <div class="card-body p-0">
            <div class="table-responsive sg-data-table-wrapper">
                <table class="table table-hover align-middle mb-0 sg-data-table">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Duration</th>
                            <th>Admission Fee</th>
                            <th>Membership Fee</th>
                            <th>Members</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($plans as $plan)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold">{{ $plan->name }}</div>
                                    @if ($plan->description)
                                        <div class="small text-muted text-truncate" style="max-width: 240px;">
                                            {{ $plan->description }}
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $plan->duration_days }} days</td>
                                <td>{{ App\Support\MoneyFormatter::format($plan->admission_fee, $gymCurrency) }}</td>
                                <td>{{ App\Support\MoneyFormatter::format($plan->membership_fee, $gymCurrency) }}</td>
                                <td>{{ number_format($plan->members_count) }}</td>
                                <td>
                                    @if ($plan->status === App\Enums\PlanStatus::Active)
                                        <span class="sg-status-badge sg-status-badge-active">Active</span>
                                    @else
                                        <span class="sg-status-badge sg-status-badge-inactive">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <x-admin.membership-plan-actions :plan="$plan" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <h3 class="h6 mb-1">No membership plans found</h3>
                                        <p class="text-muted small mb-0">Try adjusting your search or filters.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($plans->hasPages())
            <div class="card-footer bg-white border-top-0 px-4 py-3">
                {{ $plans->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
