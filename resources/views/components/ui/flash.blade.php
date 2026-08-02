@if (session('flash.message'))
    <div
        class="alert alert-{{ session('flash.type', 'info') }} alert-dismissible fade show"
        role="alert"
        x-data="{ show: true }"
        x-show="show"
        x-transition
    >
        {{ session('flash.message') }}
        <button type="button" class="btn-close" @click="show = false" aria-label="Close"></button>
    </div>
@endif
