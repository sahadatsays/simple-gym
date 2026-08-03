@extends('layouts.admin', ['heading' => 'Reports'])

@section('title', 'Reports')

@section('content')
    <x-ui.page-header
        title="Reports"
        subtitle="Business insights with filters, exports, and print-friendly views"
    />

    <div class="row g-4">
        @foreach ($reports as $report)
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('admin.reports.show', $report->value) }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 sg-report-card">
                        <div class="card-body p-4 d-flex gap-3 align-items-start">
                            <div class="sg-report-card-icon bg-primary-subtle text-primary">
                                @include('components.dashboard.icons.'.$report->icon())
                            </div>
                            <div>
                                <h2 class="h5 fw-bold mb-2 text-dark">{{ $report->label() }}</h2>
                                <p class="text-muted small mb-0">{{ $report->description() }}</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endsection

@push('styles')
    <style>
        .sg-report-card {
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .sg-report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.12) !important;
        }

        .sg-report-card-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.875rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
    </style>
@endpush
