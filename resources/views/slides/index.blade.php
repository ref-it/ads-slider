@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center gap-2 mb-3">
        <h2 class="mb-0">{{ __('All Slides') }}</h2>
        <a href="{{ route('slides.create') }}">
            <button class="btn btn-primary"><i class="fas fa-fw fa-plus"></i>&nbsp;{{ __('Create New Slide') }}</button>
        </a>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="list-group">
                @forelse ($slides as $slide)
                <a href="{{ route('slides.edit', $slide->id) }}"
                    class="list-group-item
                        list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h4>
                            <i class="fa-solid fa-fw {{ $slide->scheduleable_type === 'VI' ? 'fa-film' : 'fa-image' }}"></i>
                            {{ $slide->scheduleable->name }}
                        </h4>
                        <small>{{ $slide->user->name }}</small>
                    </div>
                    <p class="event-start">{{ $slide->start ? $slide->start->format('M d, Y') : '' }}
                        {{ $slide->end ? ' - ' . $slide->end->format('M d, Y') : '' }}
                    </p>
                    @if ($slide->recurrence_description)
                    <p class="event-recurrence"><i
                            class="fas fa-fw fa-calendar"></i>&nbsp;{{ $slide->recurrence_description }}</p>
                    @endif
                    <p class="event-start_time"><i
                            class="fas fa-fw fa-clock"></i>&nbsp;{{ substr($slide->start_time, 0, 5) }}-{{ substr($slide->end_time, 0, 5) }}
                    </p>
                    <p>
                        @if ($slide->disabled)
                        <span class="badge text-bg-danger">{{ __('Disabled') }}</span>
                        @endif
                    </p>
                    <div class="img_padding"
                        style="background-color: {{ $slide->scheduleable->bg_color }};
                                color:{{ $slide->scheduleable->color }}">
                        @if ($slide->scheduleable_type === 'VI')
                        <video height="200px" class="preview" controls muted preload="metadata">
                            <source
                                src="{{ Storage::disk('public')->url(config('ads.vid_basepath') . $slide->scheduleable->path) }}"
                                type="video/mp4">
                            {{ __('This browser does not support videos') }}
                        </video><br>
                        @else
                        <img class="preview" src="{{ $slide->scheduleable->public_path }}">
                        @endif
                        <span class="example-text">{{ __('Example Text') }}</span>
                    </div>
                </a>
                @empty
                <h3>{{ __('No slides yet, what about adding one?') }}</h3>
                @endforelse
            </div>
        </div>
    </div>
    <div class="row justify-content-center">
        {{ $slides->links() }}
    </div>
</div>
@endsection
