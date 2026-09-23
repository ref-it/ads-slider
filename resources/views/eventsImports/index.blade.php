@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center gap-2 mb-3">
        <h2 class="mb-0">Events Imports</h2>
        <a href="{{ route('eventsImports.create') }}">
            <button class="btn btn-primary"><i class="fas fa-fw fa-plus"></i>&nbsp;{{ __('Create New Events Import') }}</button>
        </a>
    </div>
    <div class="row row-cols-1 row-cols-xl-2 g-3">
        @forelse ($eventsImports as $eventsImport)
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <span class="fw-bolder">
                        <i class="event-icon me-2 fas fa-{{ $eventsImport->icon }}"
                            style="background-color:{{ config('ads.background-color', '#000000') }};
                                   color:{{ $eventsImport->color }}"></i>{{ $eventsImport->import_name }}
                    </span>
                </div>
                @if ($eventsImport->place || $eventsImport->import_disabled)
                <div class="card-body">
                    @if ($eventsImport->place)
                    <p class="event-place mb-2"><i class="fas fa-fw fa-map-marked-alt"></i>&nbsp;{{ $eventsImport->place }}</p>
                    @endif
                    @if ($eventsImport->import_disabled)
                    <span class="badge text-bg-danger">{{ __('Disabled') }}</span>
                    @endif
                </div>
                @endif
                <div @class(['card-footer', 'd-flex', 'flex-wrap', 'gap-2', 'border-top-0' => !$eventsImport->place && !$eventsImport->import_disabled])>
                    <form method="POST" action="{{ route('eventsImports.run', ['import' => $eventsImport->id]) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip"
                            data-bs-title="{{ __('Execute import') }}">
                            <i class="fas fa-fw fa-play"></i>&nbsp;{{ __('Execute import') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('eventsImports.run', ['import' => $eventsImport->id, 'force' => true]) }}">
                        @csrf
                        <input type="hidden" name="force" value="1">
                        <button type="submit" class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip"
                            data-bs-title="{{ __('All events in this import will be overwritten. This should be used, for example, if you changed the import configuration.') }}">
                            <i class="fas fa-fw fa-play"></i>&nbsp;{{ __('Force update') }}
                        </button>
                    </form>
                    <a href="{{ route('eventsImports.edit', $eventsImport->id) }}" class="btn btn-sm btn-primary ms-auto"
                        data-bs-toggle="tooltip" data-bs-title="{{ __('Edit Events Import') }}">
                        <i class="fas fa-fw fa-pen-to-square"></i>
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="col">
            <h3>{{ __('No events imports yet, what about adding one?') }}</h3>
        </div>
        @endforelse
    </div>
    <div class="row justify-content-center">
        {{ $eventsImports->links() }}
    </div>
</div>
@endsection