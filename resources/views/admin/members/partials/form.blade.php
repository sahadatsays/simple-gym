@php
    $genderOptions = App\Enums\Gender::options();
    $statusOptions = collect(App\Enums\MemberStatus::cases())
        ->mapWithKeys(fn (App\Enums\MemberStatus $status) => [$status->value => $status->label()])
        ->all();
    $planOptions = $plans->mapWithKeys(fn ($plan) => [$plan->id => $plan->name])->all();
    $isEditing = $member !== null;
@endphp

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h6 fw-semibold mb-3">{{ __('members.form.photo') }}</h2>

                <div class="sg-member-photo-preview mb-3" x-show="photoPreview" x-cloak>
                    <img
                        :src="photoPreview"
                        alt="{{ __('members.form.photo_preview_alt') }}"
                        class="sg-member-photo-preview-image"
                    >
                </div>

                <div class="sg-member-photo-placeholder mb-3" x-show="! photoPreview && ! photoProcessing">
                    @if ($member?->photo_url)
                        <x-admin.member-avatar :member="$member" size="lg" />
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                            <path d="M10.5 8.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0"/>
                            <path d="M2 4a2 2 0 0 1 2-2h6l2 2h2a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2z"/>
                        </svg>
                        <span class="small text-muted">{{ __('members.register_page.preview_after_selection') }}</span>
                    @endif
                </div>

                <div class="text-muted small mb-3" x-show="photoProcessing" x-cloak>
                    {{ __('members.register_page.optimizing_image') }}
                </div>

                <input
                    type="file"
                    name="photo"
                    id="photo"
                    accept="image/jpeg,image/png,image/webp"
                    @change="handlePhotoChange($event)"
                    @class(['form-control', 'is-invalid' => $errors->has('photo')])
                    :class="{ 'is-invalid': photoError }"
                >
                @error('photo')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <div class="invalid-feedback d-block" x-show="photoError" x-text="photoError" x-cloak></div>
                <div class="form-text">{{ __('members.register_page.photo_help_register') }}</div>

                <button
                    type="button"
                    class="btn btn-sm btn-light mt-2"
                    x-show="photoPreview"
                    @click="clearPhoto(document.getElementById('photo'))"
                    x-cloak
                >
                    {{ __('members.register_page.remove_photo') }}
                </button>

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
                        <label for="remove_photo" class="form-check-label">
                            {{ __('members.form.remove_current_photo') }}
                        </label>
                    </div>
                @endif
            </div>
        </div>

        @if ($isEditing)
            <div class="mt-4">
                @include('admin.members.partials.membership-summary', ['member' => $member])
            </div>
        @else
            <div class="alert alert-light border mt-3 mb-0">
                <div class="small text-muted">{{ __('members.form.member_id') }}</div>
                <div class="fw-semibold">{{ $nextMemberCode }}</div>
                <div class="form-text mb-0">{{ __('members.form.assigned_on_save') }}</div>
            </div>
        @endif
    </div>

    <div class="col-lg-8">
        <div class="sg-member-form-section">
            <div class="sg-member-form-section__header">
                <span class="sg-member-form-section__icon" aria-hidden="true">
                    <i class="bi bi-person-vcard"></i>
                </span>
                <div>
                    <h2 class="h6 fw-semibold mb-1">{{ __('members.form.personal_details') }}</h2>
                    <p class="text-muted small mb-0">{{ __('members.form.personal_details_help') }}</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <x-forms.input
                        :label="__('common.fields.full_name')"
                        name="name"
                        :value="$member?->name"
                        required
                    />
                </div>
                <div class="col-md-6">
                    <x-forms.input
                        :label="__('common.fields.phone')"
                        name="phone"
                        :value="$member?->phone"
                        required
                    />
                </div>
                <div class="col-md-6">
                    <x-forms.input
                        :label="__('common.fields.email_address')"
                        name="email"
                        type="email"
                        :value="$member?->email"
                    />
                </div>
                <div class="col-md-6">
                    <x-forms.select
                        :label="__('common.fields.gender')"
                        name="gender"
                        :options="$genderOptions"
                        :selected="$member?->gender?->value"
                        :placeholder="__('members.form.select_gender')"
                    />
                </div>
                <div class="col-md-6">
                    <x-forms.date-picker
                        :label="__('common.fields.date_of_birth')"
                        name="date_of_birth"
                        :value="$member?->date_of_birth?->format('Y-m-d')"
                        max-date="today"
                    />
                </div>
                <div class="col-12">
                    <x-forms.textarea
                        :label="__('common.fields.address')"
                        name="address"
                        rows="2"
                        :value="$member?->address"
                    />
                </div>
            </div>
        </div>

        <div class="sg-member-form-section mt-4">
            <div class="sg-member-form-section__header">
                <span class="sg-member-form-section__icon" aria-hidden="true">
                    <i class="bi bi-telephone-forward"></i>
                </span>
                <div>
                    <h2 class="h6 fw-semibold mb-1">{{ __('members.show.emergency_contact') }}</h2>
                    <p class="text-muted small mb-0">{{ __('members.form.emergency_contact_help') }}</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <x-forms.input
                        :label="__('members.form.contact_name')"
                        name="emergency_contact_name"
                        :value="$member?->emergency_contact_name"
                    />
                </div>
                <div class="col-md-6">
                    <x-forms.input
                        :label="__('members.form.contact_phone')"
                        name="emergency_contact_phone"
                        :value="$member?->emergency_contact_phone"
                    />
                </div>
            </div>
        </div>

        @unless ($isEditing)
            <div class="sg-member-form-section mt-4">
                <div class="sg-member-form-section__header">
                    <span class="sg-member-form-section__icon" aria-hidden="true">
                        <i class="bi bi-card-checklist"></i>
                    </span>
                    <div>
                        <h2 class="h6 fw-semibold mb-1">{{ __('members.form.membership') }}</h2>
                        <p class="text-muted small mb-0">{{ __('members.form.membership_create_help') }}</p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <x-forms.select
                            :label="__('members.show.current_plan')"
                            name="membership_plan_id"
                            :options="$planOptions"
                            :selected="$member?->membership_plan_id"
                            :placeholder="__('members.form.select_plan')"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-forms.select
                            :label="__('common.table.status')"
                            name="status"
                            :options="$statusOptions"
                            :selected="$member?->status?->value ?? 'active'"
                            required
                        />
                    </div>
                    <div class="col-md-6">
                        <x-forms.date-picker
                            :label="__('members.show.join_date')"
                            name="joined_at"
                            :value="$member?->joined_at?->format('Y-m-d') ?? now()->format('Y-m-d')"
                            required
                        />
                    </div>
                    <div class="col-md-6">
                        <x-forms.date-picker
                            :label="__('members.show.expiry_date')"
                            name="membership_expires_at"
                            :value="$member?->membership_expires_at?->format('Y-m-d')"
                            :help="__('members.form.expiry_auto_help')"
                        />
                    </div>
                </div>
            </div>
        @endunless
    </div>
</div>
