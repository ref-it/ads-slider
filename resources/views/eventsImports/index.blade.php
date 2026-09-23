@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center gap-2 mb-3">
        <h2 class="mb-0">Events Imports</h2>
        <a href="{{ route('eventsImports.create') }}">
            <button class="btn btn-primary"><i class="fas fa-fw fa-plus"></i>&nbsp;{{ __('Create New Events Import') }}</button>
        </a>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="list-group">
                @forelse ($eventsImports as $eventsImport)
                <a href="{{ route('eventsImports.edit', $eventsImport->id) }}"
                    class="list-group-item list-group-item-action">
                    <div class="d-flex w-80 justify-content-between">

                        <h4>
                            <i class="event-icon fas
                                fa-{{ $eventsImport->icon }}"
                                style="background-color:{{ config('ads.background-color', '#000000') }};
                                           color:{{ $eventsImport->color }}"></i>&nbsp;{{ $eventsImport->import_name }}
                        </h4>
                        <small>{{ $eventsImport->user->name }}</small>
                    </div>
                    <p class="event-place"><i class="fas fa-fw fa-map-marked-alt"></i>&nbsp;{{ $eventsImport->place }}</p>
                    <p>
                        @if ($eventsImport->import_disabled)
                        <span class="badge text-bg-danger">{{ __('Disabled') }}</span>
                        @endif
                    </p>
                </a>
                @empty
                <h3>{{ __('No events imports yet, what about adding one?') }}</h3>
                @endforelse
            </div>
        </div>

    </div>
    <h3>{{ __('Execute import') }}</h3>
    <div class="row">
        <div class="col-lg-9 order-lg-0">
            <div class="list-group">
                @forelse ($eventsImports as $eventsImport)
                <form method="POST" action="{{ route('eventsImports.run', ['import' => $eventsImport->id]) }}">
                    @csrf
                    <button type="submit" class="list-group-item list-group-item-action text-start w-100">
                        <div class="d-flex w-80 justify-content-between">
                            <h4>
                                <i class="event-icon fas fa-fw fa-play"></i>&nbsp;{{ $eventsImport->import_name }}
                            </h4>
                        </div>
                    </button>
                </form>
                <form method="POST" action="{{ route('eventsImports.run', ['import' => $eventsImport->id, 'force' => true]) }}">
                    @csrf
                    <input type="hidden" name="force" value="1">
                    <button type="submit" class="list-group-item list-group-item-action text-start w-100">
                        <div class="d-flex w-80 justify-content-between">
                            <h5 data-bs-toggle="tooltip" data-bs-title="{{ __('All events in this import will be overwritten. This should be used, for example, if you changed the import configuration.') }}">
                                <i class="event-icon fas fa-fw fa-play"></i>&nbsp;{{ $eventsImport->import_name }}&nbsp;({{ __('Force update') }})
                            </h5>
                        </div>
                    </button>
                </form>
                @empty
                <h3>{{ __('No events imports yet, nothing to run') }}</h3>
                @endforelse
            </div>
        </div>
    </div>
    <div class="row justify-content-center">
        {{ $eventsImports->links() }}
    </div>
</div>
@endsection