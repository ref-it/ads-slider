@if (session('flash_notification', collect())->isNotEmpty())
<div class="container mt-3">
    @foreach (session('flash_notification', collect())->toArray() as $message)
        @if ($message['overlay'])
            @include('flash::modal', [
                'modalClass' => 'flash-modal',
                'title'      => $message['title'],
                'body'       => $message['message']
            ])
        @else
            <div class="alert
                        alert-{{ $message['level'] }} alert-dismissible fade show
                        {{ $message['important'] ? 'alert-important' : '' }}"
                        role="alert"
            >
                {!! $message['message'] !!}
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="{{ __('Close') }}"
                ></button>
            </div>
        @endif
    @endforeach
</div>
@endif

{{ session()->forget('flash_notification') }}
