@extends('layouts.admin', ['heading' => $type->label()])

@section('title', $type->label())

@section('content')
    <x-ui.page-header :title="$type->label()" :subtitle="$type->description()">
        <x-slot:actions>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-light">All Reports</a>
            @include('admin.reports.partials.export-actions', ['type' => $type, 'filters' => $filters])
        </x-slot:actions>
    </x-ui.page-header>

    @include('admin.reports.partials.filters', [
        'type' => $type,
        'filters' => $filters,
        'membershipPlans' => $membershipPlans,
        'productCategories' => $productCategories,
        'memberStatuses' => $memberStatuses,
        'productStatuses' => $productStatuses,
    ])

    @include('admin.reports.partials.summary', [
        'type' => $type,
        'summary' => $payload['summary'],
    ])

    @include('admin.reports.partials.table', [
        'columns' => $payload['columns'],
        'rows' => $payload['rows'],
    ])
@endsection
