@extends('layouts.app')
@section('content')
<div class="container">
    <h2>{{ __('All Monitors') }}</h2>
    <div class="row">
        <div class="col col-lg-3 order-lg-1">
            <a href="{{ route('monitors.create') }}">
                <button class="btn btn-primary btn-block">{{ __('Create New Monitor') }}</button>
            </a>
            <p>{{ __('Click on a monitor to edit or delete it.') }}</p>
            <p>{{ __('Click on a link under a monitor element to see the result.') }}</p>
        </div>
        <div class="col-lg-9 order-lg-0">
                @forelse ($monitors as $monitor)
                <div class="card mb-3">
                    <div class="card-header d-flex w-100 justify-content-between align-items-center">
                        <span class="fw-bolder">
                            @if ($monitor->trashed())
                            <i data-bs-toggle="tooltip" data-bs-title="{{ __('Deleted') }}" class="fas fa-fw fa-trash-can-arrow-up"></i>
                            @endif
                            {{ $monitor->name }}
                        </span>
                        <small>{{ $monitor->user->name }}</small>
                    </div>
                    <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-center gap-3 pb-3 mb-3 border-bottom">
                        <i class="fas fa-fw fa-cloud-sun @if ($monitor->show_weather_forecast) feature-enabled @else feature-disabled @endif"
                            data-bs-toggle="tooltip" data-bs-placement="bottom"
                            data-bs-title="{{ __('Weather Forecast') }}"></i>
                        <i class="fas fa-fw fa-rectangle-list  @if ($monitor->show_menus) feature-enabled @else feature-disabled @endif"
                            data-bs-toggle="tooltip" data-bs-placement="bottom"
                            data-bs-title="{{ __('Menus') }}"></i>
                        <i class="fas fa-fw fa-video @if ($monitor->show_videos) feature-enabled @else feature-disabled @endif"
                            data-bs-toggle="tooltip" data-bs-placement="bottom"
                            data-bs-title="{{ __('Videos') }}"></i>
                        <i class="fas fa-fw fa-images @if ($monitor->show_pictures) feature-enabled @else feature-disabled @endif"
                            data-bs-toggle="tooltip" data-bs-placement="bottom"
                            data-bs-title="{{ __('Pictures') }}"></i>
                        <i class="fas fa-fw fa-utensils @if ($monitor->show_canteens) feature-enabled @else feature-disabled @endif"
                            data-bs-toggle="tooltip" data-bs-placement="bottom"
                            data-bs-title="{{ __('Canteens') }}"></i>
                        <i class="fas fa-fw fa-text-width @if ($monitor->show_marquee) feature-enabled @else feature-disabled @endif"
                            data-bs-toggle="tooltip" data-bs-placement="bottom"
                            data-bs-title="{{ __('Marquee text') }}"></i>
                        <i class="fas fa-fw fa-face-smile @if ($monitor->show_happy_hours) feature-enabled @else feature-disabled @endif"
                            data-bs-toggle="tooltip" data-bs-placement="bottom"
                            data-bs-title="{{ __('Happy hours') }}"></i>
                        <i class="fas fa-fw fa-stopwatch @if ($monitor->show_preparation_countdowns) feature-enabled @else feature-disabled @endif"
                            data-bs-toggle="tooltip" data-bs-placement="bottom"
                            data-bs-title="{{ __('Preparation Countdowns') }}"></i>
                        <i class="fas fa-fw fa-hourglass-half @if ($monitor->show_final_rounds) feature-enabled @else feature-disabled @endif"
                            data-bs-toggle="tooltip" data-bs-placement="bottom"
                            data-bs-title="{{ __('Final rounds') }}"></i>
                        <i class="fas fa-fw fa-hourglass-end @if ($monitor->show_we_are_closing) feature-enabled @else feature-disabled @endif"
                            data-bs-toggle="tooltip" data-bs-placement="bottom"
                            data-bs-title="{{ __('We are closing') }}"></i>
                        <i class="fas fa-fw fa-rectangle-ad @if ($monitor->show_we_are_closed_marketing) feature-enabled @else feature-disabled @endif"
                            data-bs-toggle="tooltip" data-bs-placement="bottom"
                            data-bs-title="{{ __('We are closed, Marketing') }}"></i>
                        <i class="fas fa-fw fa-door-closed @if ($monitor->show_cancelled_events) feature-enabled @else feature-disabled @endif"
                            data-bs-toggle="tooltip" data-bs-placement="bottom"
                            data-bs-title="{{ __('Cancelled events') }}"></i>
                        <i class="fas fa-fw fa-microphone-lines @if ($monitor->show_karaoke) feature-enabled @else feature-disabled @endif"
                            data-bs-toggle="tooltip" data-bs-placement="bottom"
                            data-bs-title="{{ __('Karaoke') }}"></i>
                        <i class="fas fa-fw fa-a @if ($monitor->use_animations) feature-enabled @else feature-disabled @endif"
                            data-bs-toggle="tooltip" data-bs-placement="bottom"
                            data-bs-title="{{ __('Use animations') }}"></i>
                    </div>

                    <div class="d-flex flex-wrap gap-4 mb-3">
                        <div>
                            <div class="text-muted small">{{ __('Events shown') }}</div>
                            <div>{{ $monitor->events_to_show }}</div>
                        </div>
                        <div>
                            <div class="text-muted small">{{ __('Last restart') }}</div>
                            <div>
                                <span
                                    @if ($monitor->last_restarted_at) data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{ $monitor->last_restarted_at }}" @endif
                                    @class([
                                    'badge',
                                    'me-1',
                                    'text-bg-secondary' => is_null($monitor->last_restarted_at),
                                    'text-bg-success' =>
                                    $monitor->last_restarted_at?->diffInHours(\Carbon\Carbon::now()) > 0,
                                    'text-bg-warning' =>
                                    $monitor->last_restarted_at?->diffInHours(\Carbon\Carbon::now()) >= 24,
                                    'text-bg-danger' =>
                                    $monitor->last_restarted_at?->diffInHours(\Carbon\Carbon::now()) > 72,
                                    ])>&nbsp;</span>
                                {{ $monitor->last_restarted_at?->diffForHumans() ?? __('Never') }}
                            </div>
                        </div>
                        <div>
                            <div class="text-muted small">{{ __('Data last update') }}</div>
                            <div>
                                <span
                                    @if ($monitor->last_ping) data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{ $monitor->last_ping }}" @endif
                                    @class([
                                    'badge',
                                    'me-1',
                                    'text-bg-secondary' => is_null($monitor->last_ping),
                                    'text-bg-success' =>
                                    $monitor->last_ping?->diffInHours(\Carbon\Carbon::now()) > 0,
                                    'text-bg-warning' =>
                                    $monitor->last_ping?->diffInHours(\Carbon\Carbon::now()) >= 24,
                                    'text-bg-danger' =>
                                    $monitor->last_ping?->diffInHours(\Carbon\Carbon::now()) > 72,
                                    ])>&nbsp;</span>
                                {{ $monitor->last_ping?->diffForHumans() ?? __('Never') }}
                            </div>
                        </div>
                    </div>
                    <div>
                        @if ($monitor->stats)
                        <p class="text-muted small mb-2">
                            {{ __('Data collected :when_ago', ['when_ago' => \Carbon\Carbon::parse($monitor->stats['timestamp'])->diffForHumans()]) }}
                        </p>
                        <details>
                            <summary>{{ __('Screen info') }}</summary>
                            <div>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th scope="col">{{ __('Subject') }}</th>
                                            <th scope="col">{{ __('Value') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-group-divider">
                                        <tr>
                                            <td>{{ __('Screen Resolution') }}</td>
                                            <td>{{ $monitor->stats['screen']['screenWidth'] }} x
                                                {{ $monitor->stats['screen']['screenHeight'] }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Available Resolution') }}</td>
                                            <td>{{ $monitor->stats['screen']['availWidth'] }} x
                                                {{ $monitor->stats['screen']['availHeight'] }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Screen Ratio') }}</td>
                                            <td>{{ $monitor->stats['screen']['ratio'] ?? __('N/A') }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Device Orientation') }}</td>
                                            <td>{{ $monitor->stats['screen']['orientation'] }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Pixel Depth') }}</td>
                                            <td>{{ $monitor->stats['screen']['pixelDepth'] }}</td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Color Depth') }}</td>
                                            <td>{{ $monitor->stats['screen']['colorDepth'] }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </details>

                        <details>
                            <summary>{{ __('Performance info') }}</summary>
                            <div>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th scope="col">{{ __('Subject') }}</th>
                                            <th scope="col">{{ __('Value') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-group-divider">
                                        <tr>
                                            <td>{{ __('Page total loading time') }}</td>
                                            <td>{{ round($monitor->stats['navigation']['loadEventEnd']) . __('ms') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('Page delivery time') }}</td>
                                            <td>{{ round($monitor->stats['navigation']['responseEnd']) . __('ms') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>{{ __('DOM processing') }}</td>
                                            <td>{{ round($monitor->stats['navigation']['domComplete'] - $monitor->stats['navigation']['domInteractive']) . __('ms') }}
                                            </td>
                                        </tr>
                                </table>
                            </div>
                        </details>
                        @else
                        <p class="text-muted"><i
                                class="fas fa-fw fa-info-circle"></i>&nbsp;{{ __('No information available, start the monitor on the device you want to analyze') }}
                        </p>
                        @endif
                    </div>
                    </div>
                    <div class="card-footer d-flex flex-wrap gap-2">
                        <a class="btn btn-sm btn-outline-secondary" target="_blank"
                            href="{{ route('monitors.show', $monitor->id) }}" data-bs-toggle="tooltip"
                            data-bs-placement="top" data-bs-title="{{ __('Open the monitor as a logged-in user, to test it') }}">
                            <i class="fas fa-fw fa-arrow-up-right-from-square"></i>&nbsp;{{ __('Test link') }}
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary copy-deployment-link"
                            data-link="{{ route('showEventsToken', $monitor->api_token) }}" data-bs-toggle="tooltip"
                            data-bs-placement="top" data-bs-title="{{ __('Copy the deployment link to set up the physical monitor') }}">
                            <i class="fas fa-fw fa-copy"></i>&nbsp;{{ __('Deployment link') }}
                        </button>
                        <a href="{{ route('monitors.edit', $monitor->id) }}" class="btn btn-sm btn-primary ms-auto"
                            data-bs-toggle="tooltip" data-bs-title="{{ __('Edit Monitor') }}">
                            <i class="fas fa-fw fa-pen-to-square"></i>
                        </a>
                    </div>
                </div>
                @empty
                <h3>{{ __('No monitors yet, what about adding one?') }}</h3>
                @endforelse
        </div>
    </div>
    <div class="row justify-content-center">
        {{ $monitors->links() }}
    </div>
</div>
@endsection

@section('scripts')
<script type="module">
    document.querySelectorAll('.copy-deployment-link').forEach((btn) => {
        btn.addEventListener('click', () => {
            navigator.clipboard.writeText(btn.dataset.link).then(() => {
                const icon = btn.querySelector('i');
                const original = icon.className;
                icon.className = 'fas fa-fw fa-check text-success';
                setTimeout(() => {
                    icon.className = original;
                }, 1200);
            });
        });
    });
</script>
@endsection