@props([
    'filters',
    'presets' => App\Enums\DashboardDatePreset::options(),
])

<form
    method="GET"
    action="{{ route('admin.dashboard') }}"
    class="card border-0 shadow-sm sg-dashboard-filter mb-4"
    x-data="dashboardDateFilter({
        preset: @js($filters['preset'] ?? App\Enums\DashboardDatePreset::ThisMonth->value),
        fromDate: @js($filters['from_date'] ?? now()->startOfMonth()->toDateString()),
        toDate: @js($filters['to_date'] ?? now()->endOfMonth()->toDateString()),
    })"
>
    <div class="card-body p-3 p-md-4">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2 class="h6 mb-1 fw-bold">Date Range</h2>
                <p class="text-muted small mb-0">All KPIs, charts, and tables refresh for the selected period.</p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                @foreach ($presets as $value => $label)
                    <button
                        type="button"
                        class="btn btn-sm"
                        :class="preset === @js($value) ? 'btn-primary' : 'btn-light'"
                        @click="applyPreset(@js($value))"
                    >
                        {{ $label }}
                    </button>
                @endforeach
                <button
                    type="button"
                    class="btn btn-sm"
                    :class="preset === 'custom' ? 'btn-primary' : 'btn-light'"
                    @click="preset = 'custom'"
                >
                    Custom
                </button>
            </div>
        </div>

        <input type="hidden" name="preset" x-model="preset">

        <div class="row g-3 mt-2 align-items-end" x-show="preset === 'custom'" x-cloak>
            <div class="col-6 col-md-3">
                <label for="from_date" class="form-label small fw-semibold">From</label>
                <input type="date" name="from_date" id="from_date" x-model="fromDate" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-3">
                <label for="to_date" class="form-label small fw-semibold">To</label>
                <input type="date" name="to_date" id="to_date" x-model="toDate" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-auto">
                <button type="submit" class="btn btn-primary btn-sm">Apply Range</button>
            </div>
        </div>
    </div>
</form>

@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('dashboardDateFilter', (config) => ({
                    preset: config.preset,
                    fromDate: config.fromDate,
                    toDate: config.toDate,

                    applyPreset(value) {
                        this.preset = value;
                        this.$nextTick(() => this.$root.submit());
                    },
                }));
            });
        </script>
    @endpush
@endonce
