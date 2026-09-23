<div class="container">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-start align-items-lg-center gap-2 mb-3">
        <h2 class="mb-0">{{ __('Events') }}</h2>
        <div class="d-flex flex-column flex-lg-row align-items-end align-items-lg-center gap-2">
            <a href="{{ route('events.create') }}">
                <button class="btn btn-primary"><i class="fas fa-fw fa-plus"></i>&nbsp;{{ __('Create New Event') }}</button>
            </a>
            <a href="{{ route('templates.index') }}">
                <button class="btn btn-outline-primary"><i class="fa-solid fa-fw fa-list-ul"></i>&nbsp;{{ __('Templates') }}</button>
            </a>
            @if (Auth::user()->is_realm_admin)
            <a href="{{ route('eventsImports.index') }}">
                <button class="btn btn-outline-primary"><i class="fa-solid fa-fw fa-list-ul"></i>&nbsp;{{ __('Events Imports') }}</button>
            </a>
            @endif
        </div>
    </div>
    @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>{{ __('An error has occurred') }}</strong><br>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
    </div>
    @endif
    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>{{ __('Success!') }}</strong><br>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
    </div>
    @endif
    <div class="row g-2 align-items-center">
        <div class="col-md">
            <div class="input-group">
                <span class="input-group-text" id="basic-addon1"><i class="fas fa-fw fa-magnifying-glass"></i></span>
                <input type="text" name="search" wire:model.live.debounce.200ms="search"
                    wire:keydown.debounce.200ms="searchUpdates" aria-label="Search" class="form-control"
                    @if ($this->allEvents->count() == 0) disabled placeholder="{{ __('Search is disabled as there are no events') }}" @else
                placeholder="{{ __('Type to filter events by name…') }}" @endif>
            </div>
        </div>
        <div class="col-md-auto">
            <button wire:click="clearSearch" class="btn btn-secondary">{{ __('Clear Filter') }}</button>
        </div>
    </div>
    <div class="form-check form-switch mt-3 mb-3">
        <input class="form-check-input" type="checkbox" role="switch" id="showPast"
            wire:model.live="showPast">
        <label class="form-check-label" for="showPast">{{ __('Show Past Events') }}</label>
    </div>
    <hr class="mt-0 mb-3">
    <div class="row">
        <div class="col-12">
            @forelse ($this->events as $event)
            <div wire:key="event-{{ $event->id }}" class="card mb-2">
                <a href="{{ route('events.edit', $event->id) }}"
                    class="card-header d-flex w-100 justify-content-between align-items-center text-body text-decoration-none">
                    <span class="d-flex align-items-center gap-2 {{ $event->cancelled ? 'event-cancelled' : '' }}">
                        <i class="event-icon fas fa-{{ $event->icon }}"
                            style="background-color:{{ config('ads.background-color', '#000000') }};
                               color:{{ $event->color }}"></i>
                        <span class="fw-bolder">{{ $event->name }}</span>
                    </span>
                    <small>
                        {!! $event->user
                        ? $event->user->name
                        : '<i class="fas fa-fw fa-robot" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="' .
                                    __('Imported') .
                                    '"></i>' !!}
                    </small>
                </a>
                <div class="card-body py-2">
                    <div class="d-flex flex-wrap column-gap-3 row-gap-1 mb-2">
                        <span class="event-start"><i
                                class="fas fa-fw fa-calendar"></i>&nbsp;{{ Carbon\Carbon::parse($event->real_start_date)->isoFormat('dddd LL') }}
                        </span>
                        @if ($event->recurrence_description)
                        <span class="event-recurrence"><i
                                class="fas fa-fw fa-calendar"></i>&nbsp;{{ $event->recurrence_description }}</span>
                        @endif
                        <span class="event-start_time"><i
                                class="fas fa-fw fa-clock"></i>&nbsp;{{ substr($event->start_time, 0, 5) . ' - ' . substr($event->end_time, 0, 5) }}
                        </span>
                        <span class="event-place"><i
                                class="fas fa-fw fa-map-marked-alt"></i>&nbsp;{{ $event->place }}</span>
                    </div>
                    <div class="d-flex flex-wrap align-items-center column-gap-1 row-gap-1">
                        @if ($event->schedule?->disabled)
                        @include('badges.disabled')
                        @endif
                        @if (!$event->final_round_confirmed)
                        @include('badges.no_final_round')
                        @endif
                        @if ($event->not_closing)
                        @include('badges.not_closing')
                        @endif
                        @if ($event->is_karaoke)
                        @include('badges.karaoke')
                        @endif
                        @if ($event->is_protected)
                        @include('badges.protected')
                        @endif
                        @if ($event->link)
                        @include('badges.qr', ['event' => $event])
                        @endif
                        @if ($event->import_id)
                        @if ($event->created_at->gte($event->updated_at))
                        @include('badges.imported', ['event' => $event])
                        @else
                        @include('badges.imported_edited', ['event' => $event])
                        @endif
                        @endif
                        <a href="{{ route('events.edit', $event->id) }}" class="btn btn-sm btn-primary ms-auto"
                            data-bs-toggle="tooltip" data-bs-title="{{ __('Edit Event') }}">
                            <i class="fas fa-fw fa-pen-to-square"></i>
                        </a>
                        <button class="btn btn-sm btn-danger"
                            wire:click="deleteEvent({{ $event->id }})"
                            wire:confirm="{{ __('Are you sure you want to delete the event ":EVENT_NAME"?', ['EVENT_NAME' => $event->name]) }}"
                            data-bs-toggle="tooltip" data-bs-title="{{ __('Delete Event') }}">
                            <i class="fas fa-fw fa-trash-can"></i>
                        </button>
                    </div>
                </div>
            </div>
            @empty
            @if ($search)
            <h3>{{ __('No events found') }}</h3>
            <button wire:click="clearSearch"
                class="btn btn-primary col-3">{{ __('Clear Search') }}</button>
            @else
            <h3>{{ __('No events found, how about creating one?') }}</h3>
            @endif
            @endforelse
        </div>
    </div>
    <div class="row justify-content-center">
        {{ $this->events->links() }}
    </div>
</div>