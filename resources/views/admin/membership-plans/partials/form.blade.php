@php
    $featuresText = old(
        'features_text',
        collect($plan?->features ?? [])->implode("\n")
    );
@endphp

<div class="row">
    <div class="col-lg-8">
        <x-forms.input
            label="Plan name"
            name="name"
            placeholder="Monthly Standard"
            :value="$plan?->name"
            required
        />

        <x-forms.input
            label="Duration (days)"
            name="duration_days"
            type="number"
            min="1"
            placeholder="30"
            :value="$plan?->duration_days"
            required
            help="Membership length stored in days."
        />

        <div class="row">
            <div class="col-md-6">
                <x-forms.money-input
                    label="Admission fee"
                    name="admission_fee"
                    :value="$plan?->admission_fee ?? ($defaultAdmissionFee ?? 0)"
                    required
                />
            </div>
            <div class="col-md-6">
                <x-forms.money-input
                    label="Membership fee"
                    name="membership_fee"
                    :value="$plan?->membership_fee"
                    required
                />
            </div>
        </div>

        <x-forms.textarea
            label="Description"
            name="description"
            rows="3"
            placeholder="Brief plan overview..."
            :value="$plan?->description"
        />

        <x-forms.textarea
            label="Features"
            name="features_text"
            rows="5"
            placeholder="One feature per line"
            :value="$featuresText"
            help="Enter each feature on a separate line."
        />

        <x-forms.select
            label="Status"
            name="status"
            :options="['active' => 'Active', 'inactive' => 'Inactive']"
            :selected="old('status', $plan?->status?->value ?? 'active')"
            required
        />
    </div>
</div>
