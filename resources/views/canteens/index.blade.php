@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center gap-2 mb-3">
        <h2 class="mb-0">{{ __('All Canteens') }}</h2>
        <a href="{{ route('canteens.create') }}">
            <button class="btn btn-primary"><i class="fas fa-fw fa-plus"></i>&nbsp;{{ __('Create New Canteen') }}</button>
        </a>
    </div>
    <div class="row row-cols-1 row-cols-xl-2 g-3">
        @forelse ($canteens as $slide)
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <span class="fw-bolder">{{ $slide->scheduleable->name }}</span>
                </div>
                <div class="card-body">
                    @if ($slide->recurrence_description)
                    <p class="event-start mb-2"><i
                            class="fas fa-fw fa-calendar"></i>&nbsp;{{ $slide->recurrence_description }}</p>
                    @endif
                    <p class="event-start_time mb-0"><i
                            class="fas fa-fw fa-clock"></i>&nbsp;{{ substr($slide->start_time, 0, 5) }}-{{ substr($slide->end_time, 0, 5) }}
                    </p>
                    @if ($slide->disabled)
                    <span class="badge text-bg-danger">{{ __('Disabled') }}</span>
                    @endif
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <a href="{{ route('canteens.edit', $slide->scheduleable_id) }}" class="btn btn-sm btn-primary"
                        data-bs-toggle="tooltip" data-bs-title="{{ __('Edit Canteen') }}">
                        <i class="fas fa-fw fa-pen-to-square"></i>
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="col">
            <h3>{{ __('No canteens yet, what about adding one?') }}</h3>
        </div>
        @endforelse
    </div>
    <div class="row justify-content-center">
        {{ $canteens->links() }}
    </div>
</div>
@endsection
