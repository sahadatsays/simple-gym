@props(['type', 'filters'])

@php
    $baseQuery = array_filter([
        'from_date' => $filters['from_date'] ?? null,
        'to_date' => $filters['to_date'] ?? null,
        'membership_plan_id' => $filters['membership_plan_id'] ?? null,
        'status' => $filters['status'] ?? null,
        'category_id' => $filters['category_id'] ?? null,
        'days' => $filters['days'] ?? null,
    ], fn ($value) => filled($value));
@endphp

<div class="d-flex flex-wrap gap-2">
    <a
        href="{{ route('admin.reports.show', array_merge(['report' => $type->value], $baseQuery, ['export' => 'print'])) }}"
        class="btn btn-light"
        target="_blank"
    >
        Print
    </a>
    <a
        href="{{ route('admin.reports.show', array_merge(['report' => $type->value], $baseQuery, ['export' => 'pdf'])) }}"
        class="btn btn-light"
    >
        Export PDF
    </a>
    <a
        href="{{ route('admin.reports.show', array_merge(['report' => $type->value], $baseQuery, ['export' => 'excel'])) }}"
        class="btn btn-light"
    >
        Export Excel
    </a>
</div>
