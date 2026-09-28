@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center gap-2 mb-3">
        <h2 class="mb-0">{{ __('Slides') }}</h2>
        <a href="{{ route('slides.create') }}">
            <button class="btn btn-primary"><i class="fas fa-fw fa-plus"></i>&nbsp;{{ __('Create New Slide') }}</button>
        </a>
    </div>
    @if ($slides->isEmpty())
    <div class="alert alert-info alert-important"><i class="fas fa-fw fa-info-circle"></i>&nbsp;{{ __('No slides yet, what about adding one?') }}</div>
    @else
    <div class="row row-cols-1 row-cols-xl-2 g-3">
        @foreach ($slides as $slide)
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <span class="fw-bolder">
                        <i class="fa-solid fa-fw {{ $slide->scheduleable_type === 'VI' ? 'fa-film' : 'fa-image' }}"></i>
                        {{ $slide->scheduleable->name }}
                    </span>
                </div>
                <div class="card-body">
                    <p class="event-start mb-2">{{ $slide->start ? $slide->start->format('M d, Y') : '' }}
                        {{ $slide->end ? ' - ' . $slide->end->format('M d, Y') : '' }}
                    </p>
                    @if ($slide->recurrence_description)
                    <p class="event-recurrence mb-2"><i
                            class="fas fa-fw fa-calendar"></i>&nbsp;{{ $slide->recurrence_description }}</p>
                    @endif
                    <p class="event-start_time mb-2"><i
                            class="fas fa-fw fa-clock"></i>&nbsp;{{ substr($slide->start_time, 0, 5) }}-{{ substr($slide->end_time, 0, 5) }}
                    </p>
                    @if ($slide->disabled)
                    <span class="badge text-bg-danger mb-2">{{ __('Disabled') }}</span>
                    @endif
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
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <a href="{{ route('slides.edit', $slide->id) }}" class="btn btn-sm btn-primary"
                        data-bs-toggle="tooltip" data-bs-title="{{ __('Edit Slide') }}">
                        <i class="fas fa-fw fa-pen-to-square"></i>
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
    <div class="row justify-content-center">
        {{ $slides->links() }}
    </div>
</div>
@endsection
