@extends('layouts.app')
@section('content')
<div class="container">
    <h2>{{ __('All Canteens') }}</h2>
    <div class="row">
        <div class="col col-lg-3 order-lg-1">
            <a href="{{ route('canteens.create') }}">
                <button class="btn btn-primary btn-block">{{ __('Create New Canteen') }}</button>
            </a>
            <p>{{ __('Click on a canteen to edit or delete it.') }}</p>
        </div>
        <div class="col-lg-9 order-lg-0">
            <div class="list-group">
                @forelse ($canteens as $slide)
                <a href="{{ route('canteens.edit', $slide->scheduleable_id) }}"
                    class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h4>{{ $slide->scheduleable->name }}</h4>
                        <small>{{ $slide->user->name }}</small>
                    </div>
                    <p class="event-start">{{ $slide->recurrence_description }}</p>
                    <p class="event-start_time"><i
                            class="fas fa-fw fa-clock"></i>&nbsp;{{ substr($slide->start_time, 0, 5) }}-{{ substr($slide->end_time, 0, 5) }}
                    </p>
                    <p>
                        @if ($slide->disabled)
                        <span class="badge text-bg-danger">{{ __('Disabled') }}</span>
                        @endif
                    </p>
                </a>
                @empty
                <h3>{{ __('No canteens yet, what about adding one?') }}</h3>
                @endforelse
            </div>
        </div>
    </div>
    <div class="row justify-content-center">
        {{ $canteens->links() }}
    </div>
</div>
@endsection
