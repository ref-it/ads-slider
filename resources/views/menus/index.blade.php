@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center gap-2 mb-3">
        <h2 class="mb-0">{{__('All Menus')}}</h2>
        <a href="{{ route('menus.create') }}">
            <button class="btn btn-primary"><i class="fas fa-fw fa-plus"></i>&nbsp;{{__('Create New Menu')}}</button>
        </a>
    </div>
    <div class="row row-cols-1 row-cols-xl-2 g-3">
        @forelse ($menus as $menu)
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <span class="fw-bolder">{{ $menu->name }}</span>
                </div>
                <div class="card-body">
                    @if (count($menu->events) > 0)
                    <details>
                        <summary>{{__('Used in these Events')}}</summary>
                        @foreach ($menu->events as $e)
                        <a href="{{ route('events.edit', $e->id) }}">
                            <p>{{ $e->name }}</p>
                        </a>
                        @endforeach
                    </details>
                    @else
                    <p class="text-muted mb-0"><i class="fas fa-fw fa-info-circle"></i>&nbsp;{{__('Currently not used in any event.')}} <a href="{{ route('events.index') }}">{{__('Assign it to one!')}}</a></p>
                    @endif
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <a href="{{ route('menus.edit', $menu->id) }}" class="btn btn-sm btn-primary"
                        data-bs-toggle="tooltip" data-bs-title="{{ __('Edit Menu') }}">
                        <i class="fas fa-fw fa-pen-to-square"></i>
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="col">
            <h3>{{ __('No menus yet, what about adding one?') }}</h3>
        </div>
        @endforelse
    </div>
    <div class="row justify-content-center">
        {{ $menus->links() }}
    </div>
</div>
@endsection