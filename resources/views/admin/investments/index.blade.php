@extends('layouts.admin', ['heading' => 'Investments'])

@section('title', 'Investments')

@section('content')
    <x-ui.page-header title="Investments" subtitle="Track and manage gym investments">
        <x-slot:actions>
            @can('create', App\Models\Investment::class)
                <a href="{{ route('admin.investments.create') }}" class="btn btn-primary">
                    Add Investment
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <x-admin.filter-bar class="mb-4">
        <form action="{{ route('admin.investments.index') }}" method="GET" class="sg-filter-grid">
            <x-admin.filter-field label="Search" for="search">
                <input
                    type="search"
                    name="search"
                    id="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Investment no. or description..."
                    class="form-control ps-2"
                >
            </x-admin.filter-field>

            <x-admin.filter-field label="Category" for="investment_category_id">
                <select name="investment_category_id" id="investment_category_id" class="form-select">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['investment_category_id'] ?? '') === (string) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Method" for="payment_method">
                <select name="payment_method" id="payment_method" class="form-select">
                    <option value="">All methods</option>
                    @foreach (App\Enums\PaymentMethod::options() as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['payment_method'] ?? '') === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="From" for="from_date">
                <input type="date" name="from_date" id="from_date" value="{{ $filters['from_date'] ?? '' }}" class="form-control">
            </x-admin.filter-field>

            <x-admin.filter-field label="To" for="to_date">
                <input type="date" name="to_date" id="to_date" value="{{ $filters['to_date'] ?? '' }}" class="form-control">
            </x-admin.filter-field>

            <x-admin.filter-field label="Actions" class="sg-filter-actions-field">
                <div class="sg-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.investments.index') }}" class="btn btn-light">Reset</a>
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
                            <th class="ps-4">Investment No.</th>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Method</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($investments as $investment)
                            <tr>
                                <td class="ps-4">
                                    <a href="{{ route('admin.investments.show', $investment) }}" class="fw-semibold text-decoration-none">
                                        {{ $investment->investment_number }}
                                    </a>
                                    @if ($investment->description)
                                        <div class="small text-muted text-truncate" style="max-width: 240px;">
                                            {{ $investment->description }}
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $investment->invested_at->format('M j, Y') }}</td>
                                <td>{{ $investment->category?->name ?? '—' }}</td>
                                <td>{{ $investment->payment_method->label() }}</td>
                                <td class="text-end">{{ App\Support\MoneyFormatter::format($investment->amount, $gymCurrency) }}</td>
                                <td class="text-end pe-4">
                                    <x-admin.investment-actions :investment="$investment" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="sg-empty-state">
                                        <h3 class="h6 mb-1">No investments found</h3>
                                        <p class="text-muted small mb-0">Try adjusting your search or filters.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($investments->hasPages())
            <div class="card-footer bg-white border-top-0 px-4 py-3">
                {{ $investments->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
