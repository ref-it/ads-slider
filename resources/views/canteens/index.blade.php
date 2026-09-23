@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center gap-2 mb-3">
        <h2 class="mb-0">{{ __('All Canteens') }}</h2>
        <a href="{{ route('canteens.create') }}">
            <button class="btn btn-primary"><i class="fas fa-fw fa-plus"></i>&nbsp;{{ __('Create New Canteen') }}</button>
        </a>
    </div>
    <div class="row">
        <div class="col-12">
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
