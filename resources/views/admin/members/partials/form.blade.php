@php
    $genderOptions = App\Enums\Gender::options();
    $statusOptions = collect(App\Enums\MemberStatus::cases())
        ->mapWithKeys(fn (App\Enums\MemberStatus $status) => [$status->value => $status->label()])
        ->all();
    $planOptions = $plans->mapWithKeys(fn ($plan) => [$plan->id => $plan->name])->all();
@endphp

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 fw-semibold mb-3">Photo</h2>

                @if ($member?->photo_url)
                    <div class="text-center mb-3">
                        <x-admin.member-avatar :member="$member" size="lg" />
                    </div>
                @endif

                <input
                    type="file"
                    name="photo"
                    id="photo"
                    accept="image/jpeg,image/png,image/webp"
                    @class(['form-control', 'is-invalid' => $errors->has('photo')])
                >
                @error('photo')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <div class="form-text">JPG, PNG or WebP. Max 2 MB.</div>

                @if ($member?->photo_url)
                    <div class="form-check mt-3">
                        <input
                            type="checkbox"
                            name="remove_photo"
                            id="remove_photo"
                            value="1"
                            class="form-check-input"
                            @checked(old('remove_photo'))
                        >
                        <label for="remove_photo" class="form-check-label">Remove current photo</label>
                    </div>
                @endif
            </div>
        </div>

        @unless ($member)
            <div class="alert alert-light border mt-3 mb-0">
                <div class="small text-muted">Member ID</div>
                <div class="fw-semibold">{{ $nextMemberCode }}</div>
                <div class="form-text mb-0">Assigned automatically on save.</div>
            </div>
        @endunless
    </div>

    <div class="col-lg-8">
        <div class="row">
            <div class="col-md-6">
                <x-forms.input
                    label="Full name"
                    name="name"
                    :value="$member?->name"
                    required
                />
            </div>
            <div class="col-md-6">
                <x-forms.input
                    label="Phone"
                    name="phone"
                    :value="$member?->phone"
                    required
                />
            </div>
            <div class="col-md-6">
                <x-forms.input
                    label="Email"
                    name="email"
                    type="email"
                    :value="$member?->email"
                />
            </div>
            <div class="col-md-6">
                <x-forms.select
                    label="Gender"
                    name="gender"
                    :options="$genderOptions"
                    :selected="$member?->gender?->value"
                    placeholder="Select gender"
                />
            </div>
            <div class="col-md-6">
                <x-forms.date-picker
                    label="Date of birth"
                    name="date_of_birth"
                    :value="$member?->date_of_birth?->format('Y-m-d')"
                    max-date="today"
                />
            </div>
            <div class="col-12">
                <x-forms.textarea
                    label="Address"
                    name="address"
                    rows="2"
                    :value="$member?->address"
                />
            </div>
        </div>

        <hr class="my-4">

        <h2 class="h6 fw-semibold mb-3">Emergency Contact</h2>
        <div class="row">
            <div class="col-md-6">
                <x-forms.input
                    label="Contact name"
                    name="emergency_contact_name"
                    :value="$member?->emergency_contact_name"
                />
            </div>
            <div class="col-md-6">
                <x-forms.input
                    label="Contact phone"
                    name="emergency_contact_phone"
                    :value="$member?->emergency_contact_phone"
                />
            </div>
        </div>

        <hr class="my-4">

        <h2 class="h6 fw-semibold mb-3">Membership</h2>
        <div class="row">
            <div class="col-md-6">
                <x-forms.select
                    label="Current plan"
                    name="membership_plan_id"
                    :options="$planOptions"
                    :selected="$member?->membership_plan_id"
                    placeholder="Select plan"
                />
            </div>
            <div class="col-md-6">
                <x-forms.select
                    label="Status"
                    name="status"
                    :options="$statusOptions"
                    :selected="$member?->status?->value ?? 'active'"
                    required
                />
            </div>
            <div class="col-md-6">
                <x-forms.date-picker
                    label="Join date"
                    name="joined_at"
                    :value="$member?->joined_at?->format('Y-m-d') ?? now()->format('Y-m-d')"
                    required
                />
            </div>
            <div class="col-md-6">
                <x-forms.date-picker
                    label="Expiry date"
                    name="membership_expires_at"
                    :value="$member?->membership_expires_at?->format('Y-m-d')"
                    help="Leave blank to auto-calculate from the selected plan."
                />
            </div>
        </div>
    </div>
</div>
