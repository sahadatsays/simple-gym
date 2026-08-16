@props([
    'type',
    'filters',
    'membershipPlans' => collect(),
    'productCategories' => [],
    'memberStatuses' => [],
    'productStatuses' => [],
])

<x-admin.filter-bar class="mb-4">
    <form action="{{ route('admin.reports.show', $type->value) }}" method="GET" class="sg-filter-grid">
        @unless (in_array($type->value, ['stock'], true))
            <x-admin.filter-field label="From" for="from_date">
                <input type="date" name="from_date" id="from_date" value="{{ $filters['from_date'] ?? '' }}" class="form-control">
            </x-admin.filter-field>

            <x-admin.filter-field label="To" for="to_date">
                <input type="date" name="to_date" id="to_date" value="{{ $filters['to_date'] ?? '' }}" class="form-control">
            </x-admin.filter-field>
        @endunless

        @if (in_array($type->value, ['membership', 'expired-members', 'upcoming-expiry'], true))
            <x-admin.filter-field label="Plan" for="membership_plan_id">
                <select name="membership_plan_id" id="membership_plan_id" class="form-select">
                    <option value="">All plans</option>
                    @foreach ($membershipPlans as $plan)
                        <option value="{{ $plan->id }}" @selected(($filters['membership_plan_id'] ?? null) == $plan->id)>
                            {{ $plan->name }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>
        @endif

        @if ($type->value === 'membership')
            <x-admin.filter-field label="Status" for="status">
                <select name="status" id="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach ($memberStatuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>
        @endif

        @if ($type->value === 'product-sales')
            <x-admin.filter-field label="Category" for="category_id">
                <select name="category_id" id="category_id" class="form-select">
                    <option value="">All categories</option>
                    @foreach ($productCategories as $category)
                        <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? null) == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>
        @endif

        @if ($type->value === 'stock')
            <x-admin.filter-field label="Category" for="category_id">
                <select name="category_id" id="category_id" class="form-select">
                    <option value="">All categories</option>
                    @foreach ($productCategories as $category)
                        <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? null) == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>

            <x-admin.filter-field label="Status" for="status">
                <select name="status" id="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach ($productStatuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </x-admin.filter-field>
        @endif

        <x-admin.filter-field label="Actions" class="sg-filter-actions-field">
            <div class="sg-filter-actions">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="{{ route('admin.reports.show', $type->value) }}" class="btn btn-light">Reset</a>
            </div>
        </x-admin.filter-field>
    </form>
</x-admin.filter-bar>
