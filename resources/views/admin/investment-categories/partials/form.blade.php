@props(['category'])

<div class="row">
    <div class="col-lg-8">
        <x-forms.input
            label="Category name"
            name="name"
            placeholder="Equipment"
            :value="$category?->name"
            required
        />

        <x-forms.textarea
            label="Description"
            name="description"
            rows="3"
            :value="$category?->description"
            placeholder="Optional description for internal reference"
        />

        <div class="row">
            <div class="col-md-6">
                <x-forms.input
                    label="Sort order"
                    name="sort_order"
                    type="number"
                    min="0"
                    max="9999"
                    :value="$category?->sort_order ?? 0"
                    help="Lower numbers appear first in lists and filters."
                />
            </div>
            <div class="col-md-6">
                <label class="form-label d-block">Status</label>
                <div class="form-check form-switch">
                    <input
                        type="checkbox"
                        name="is_active"
                        id="is_active"
                        value="1"
                        class="form-check-input"
                        @checked(old('is_active', $category?->is_active ?? true))
                    >
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
                <div class="form-text">Inactive categories are hidden from investment forms.</div>
            </div>
        </div>
    </div>
</div>
