@extends('layouts.admin', ['heading' => __('members.edit')])

@section('title', __('members.edit'))

@section('content')
    <x-ui.page-header :title="__('members.edit')" :subtitle="__('members.edit_subtitle_profile')">
        <x-slot:actions>
            <a href="{{ route('admin.members.show', $member) }}" class="btn btn-light">
                {{ __('members.show.view_profile') }}
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card class="border-0 shadow-sm">
        <form
            action="{{ route('admin.members.update', $member) }}"
            method="POST"
            enctype="multipart/form-data"
            x-data="memberEdit({ initialPhotoUrl: @js($member->photo_url) })"
        >
            @csrf
            @method('PUT')

            @include('admin.members.partials.form', ['member' => $member])

            <div class="sg-form-actions d-flex flex-wrap gap-2 pt-4 mt-4 border-top">
                <x-ui.button type="submit">{{ __('common.actions.save') }}</x-ui.button>
                <a href="{{ route('admin.members.show', $member) }}" class="btn btn-light">{{ __('common.actions.cancel') }}</a>
            </div>
        </form>
    </x-ui.card>
@endsection
