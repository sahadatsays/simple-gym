@extends('layouts.admin', ['heading' => 'Investment Details'])

@section('title', $investment->investment_number)

@section('content')
    <x-ui.page-header :title="$investment->investment_number" subtitle="Investment details">
        <x-slot:actions>
            @can('update', $investment)
                <a href="{{ route('admin.investments.edit', $investment) }}" class="btn btn-primary">Edit</a>
            @endcan
            <a href="{{ route('admin.investments.index') }}" class="btn btn-light">Back to Investments</a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-semibold mb-3">Investment</h2>
                    <dl class="row sg-profile-list mb-0">
                        <dt class="col-sm-5">Investment No.</dt>
                        <dd class="col-sm-7">{{ $investment->investment_number }}</dd>

                        <dt class="col-sm-5">Date</dt>
                        <dd class="col-sm-7">{{ $investment->invested_at->format('M j, Y') }}</dd>

                        <dt class="col-sm-5">Category</dt>
                        <dd class="col-sm-7">{{ $investment->category?->name ?? '—' }}</dd>

                        <dt class="col-sm-5">Amount</dt>
                        <dd class="col-sm-7">{{ App\Support\MoneyFormatter::format($investment->amount, $gymCurrency) }}</dd>

                        <dt class="col-sm-5">Payment method</dt>
                        <dd class="col-sm-7">{{ $investment->payment_method->label() }}</dd>

                        @if ($investment->description)
                            <dt class="col-sm-5">Description</dt>
                            <dd class="col-sm-7">{{ $investment->description }}</dd>
                        @endif

                        @if ($investment->creator)
                            <dt class="col-sm-5">Created by</dt>
                            <dd class="col-sm-7">{{ $investment->creator->name }}</dd>
                        @endif

                        <dt class="col-sm-5">Recorded</dt>
                        <dd class="col-sm-7">{{ $investment->created_at?->format('M j, Y g:i A') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 fw-semibold mb-3">Attachment</h2>

                    @if ($investment->attachment_path)
                        <p class="text-muted small mb-3">Supporting document uploaded with this investment.</p>
                        <a href="{{ $investment->attachment_url }}" class="btn btn-light" target="_blank" rel="noopener">
                            View Attachment
                        </a>
                    @else
                        <p class="text-muted mb-0">No attachment uploaded.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
