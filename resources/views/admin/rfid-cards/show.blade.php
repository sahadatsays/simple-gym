@extends('layouts.admin', ['heading' => 'RFID Card'])

@section('title', $card->card_number)

@section('content')
    <x-ui.page-header title="{{ $card->card_number }}" subtitle="Card details and assignment history">
        <x-slot:actions>
            <a href="{{ route('admin.rfid-cards.index') }}" class="btn btn-light">Back to cards</a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Status</div>
                    <div class="mt-1">
                        <span class="sg-status-badge {{ $card->status->badgeClass() }}">{{ $card->status->label() }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Card Fee</div>
                    <div class="fw-semibold mt-1">{{ App\Support\MoneyFormatter::format($card->card_fee, $gymCurrency) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Created by</div>
                    <div class="fw-semibold mt-1">{{ $card->creator?->name ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm sg-data-table-card">
        <div class="card-header bg-white border-0 pt-4 px-4">
            <h2 class="h6 fw-bold mb-0">Assignment History</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive sg-data-table-wrapper">
                <table class="table table-hover align-middle mb-0 sg-data-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th class="d-none d-md-table-cell">Issue Date</th>
                            <th class="d-none d-md-table-cell">Return Date</th>
                            <th class="d-none d-lg-table-cell">Card Fee</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($card->assignments as $assignment)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold">{{ $assignment->member?->name ?? '—' }}</div>
                                    <div class="small text-muted">{{ $assignment->member?->member_code }}</div>
                                </td>
                                <td class="d-none d-md-table-cell">{{ $assignment->issue_date?->format('M j, Y') }}</td>
                                <td class="d-none d-md-table-cell">{{ $assignment->return_date?->format('M j, Y') ?? '—' }}</td>
                                <td class="d-none d-lg-table-cell">{{ App\Support\MoneyFormatter::format($assignment->card_fee, $gymCurrency) }}</td>
                                <td>
                                    <span class="sg-status-badge {{ $assignment->status->badgeClass() }}">{{ $assignment->status->label() }}</span>
                                    @if ($assignment->invoice)
                                        <div class="small mt-1">
                                            <a href="{{ route('admin.invoices.show', $assignment->invoice) }}">{{ $assignment->invoice->invoice_number }}</a>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No assignments yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
