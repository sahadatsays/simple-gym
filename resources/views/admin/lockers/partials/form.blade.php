<x-forms.input
    label="Locker number"
    name="locker_number"
    placeholder="A-12"
    :value="$locker?->locker_number"
    required
/>

<x-forms.input
    label="Location"
    name="location"
    placeholder="Ground floor, men's area"
    :value="$locker?->location"
/>

<x-forms.input
    label="Type / Category"
    name="category"
    placeholder="Standard, large, premium"
    :value="$locker?->category"
/>

<x-forms.money-input
    label="Monthly fee"
    name="monthly_fee"
    :value="$locker?->monthly_fee ?? 0"
    required
/>

<x-forms.select
    label="Status"
    name="status"
    :options="App\Enums\LockerStatus::options()"
    :selected="old('status', $locker?->status?->value ?? App\Enums\LockerStatus::Available->value)"
    required
/>

<x-forms.textarea
    label="Notes"
    name="notes"
    rows="4"
    placeholder="Optional notes about this locker"
    :value="$locker?->notes"
/>
