@if ($picture)
<hr>
<div class="d-flex justify-content-between align-items-center mb-2">
    <h3>{{ __('Formats') }}</h3>
    <div class="d-flex align-items-center">
        <label for="preview_screen_format" class="me-2 mb-0 text-nowrap">{{ __('Preview Screen Format') }}</label>
        <select id="preview_screen_format" class="form-control form-control-sm" style="width: auto;">
            <option value="1.777777">16:9</option>
            <option value="1.6">16:10</option>
            <option value="1.333333">4:3</option>
            <option value="1">1:1</option>
            <option value="0.5625">9:16</option>
        </select>
    </div>
</div>
<h5>{{ __('The best option for each monitor ratio will be selected automatically.') }}</h5>
<div class="row">
    @foreach ($picture->sources as $source)
    <div class="col-12 col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header">
                <span class="badge bg-secondary">{{ $source->ratio }}</span>&nbsp;{{ $source->width }}x{{ $source->height }}<br>
            </div>
            <div class="card-body">
                <div class="mb-2 preview_box d-flex justify-content-center align-items-center position-relative mx-auto" style="background-color: {{ $picture->bg_color }}; color: {{ $picture->color }}; height: 150px; width: 266px; overflow: hidden;">
                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(config('ads.pic_basepath') . $source->path) }}" target="_blank" class="d-flex w-100 h-100 justify-content-center align-items-center">
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(config('ads.pic_basepath') . $source->path) }}" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                    </a>
                    <span class="preview_text" data-source-id="{{ $source->id }}" style="position: absolute; font-weight: bold; background-color: transparent;">12:34</span>
                </div>

                <div class="form-floating d-flex align-items-center">
                    <select class="form-select w-100 form-control-sm clock-location-selector" data-source-id="{{ $source->id }}" id="floatingSelect{{ $source->id }}">
                        <option value="0" {{ $source->clock_location == 0 ? 'selected' : '' }}> {{ __('Disabled') }}</option>
                        <option value="1" {{ $source->clock_location == 1 ? 'selected' : '' }}>↖ {{ __('Top left') }}</option>
                        <option value="2" {{ $source->clock_location == 2 ? 'selected' : '' }}>↑ {{ __('Top middle') }}</option>
                        <option value="3" {{ $source->clock_location == 3 ? 'selected' : '' }}>↗ {{ __('Top right') }}</option>
                        <option value="4" {{ $source->clock_location == 4 ? 'selected' : '' }}>→ {{ __('Right middle') }}</option>
                        <option value="5" {{ $source->clock_location == 5 ? 'selected' : '' }}>↘ {{ __('Bottom right') }} ({{ __('default') }})</option>
                        <option value="6" {{ $source->clock_location == 6 ? 'selected' : '' }}>↓ {{ __('Bottom middle') }}</option>
                        <option value="7" {{ $source->clock_location == 7 ? 'selected' : '' }}>↙ {{ __('Bottom left') }}</option>
                        <option value="8" {{ $source->clock_location == 8 ? 'selected' : '' }}>← {{ __('Left middle') }}</option>
                        <option value="9" {{ $source->clock_location == 9 ? 'selected' : '' }}>⊚ {{ __('Center') }}</option>
                    </select>
                    <label for="floatingSelect{{ $source->id }}">{{ __('Clock position') }}</label>
                </div>
            </div>
            <div class="card-footer text-body-secondary">
                <form action="{{ route('pics.destroySource', $source) }}" method="POST" data-confirm="{{ __('Delete this format?') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" {{ $picture->sources->count() <= 1 ? 'disabled' : '' }}>{{ __('Delete format') }}</button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>

<form action="{{ route('pics.storeSource', $picture) }}" method="POST" enctype="multipart/form-data" class="card card-body mt-3">
    @csrf
    <h5>{{ __('Add new format') }}</h5>
    <div class="input-group">
        <input type="file" class="form-control" name="upload" required accept="image/*">
        <button class="btn btn-primary" type="submit">{{ __('Upload') }}</button>
    </div>
</form>

<script @cspNonce>
    (function() {
        function setPosition(text, val) {
            text.css('top', '').css('bottom', '').css('left', '').css('right', '').css('transform', '');

            switch (parseInt(val)) {
                case 0: // Disabled
                    text.hide();
                    break;
                case 1: // Top Left
                    text.show().css('top', '10px').css('left', '10px');
                    break;
                case 2: // Top Middle
                    text.show().css('top', '10px').css('left', '50%').css('transform', 'translateX(-50%)');
                    break;
                case 3: // Top Right
                    text.show().css('top', '10px').css('right', '10px');
                    break;
                case 4: // Right Middle
                    text.show().css('top', '50%').css('right', '10px').css('transform', 'translateY(-50%)');
                    break;
                case 5: // Bottom Right
                    text.show().css('bottom', '10px').css('right', '10px');
                    break;
                case 6: // Bottom Middle
                    text.show().css('bottom', '10px').css('left', '50%').css('transform', 'translateX(-50%)');
                    break;
                case 7: // Bottom Left
                    text.show().css('bottom', '10px').css('left', '10px');
                    break;
                case 8: // Left Middle
                    text.show().css('top', '50%').css('left', '10px').css('transform', 'translateY(-50%)');
                    break;
                case 9: // Center
                    text.show().css('top', '50%').css('left', '50%').css('transform', 'translate(-50%, -50%)');
                    break;
            }
        }

        function updateClockPosition(sourceId, val) {
            const text = $(`.preview_text[data-source-id="${sourceId}"]`);
            setPosition(text, val);
        }

        function updatePreviewRatio() {
            const ratio = parseFloat($('#preview_screen_format').val());
            const height = 120;
            const width = height * ratio;
            $('.preview_box').width(width);
        }
        $('#preview_screen_format').change(updatePreviewRatio);
        updatePreviewRatio();

        $('.clock-location-selector').each(function() {
            const sourceId = $(this).data('source-id');
            updateClockPosition(sourceId, $(this).val());

            $(this).change((e) => {
                const select = $(e.target);
                const val = select.val();
                updateClockPosition(sourceId, val);

                select.prop('disabled', true);
                $.ajax({
                    url: `{{ url('pics/source') }}/${sourceId}`,
                    type: 'PUT',
                    data: {
                        _token: "{{ csrf_token() }}",
                        clock_location: val
                    },
                    success: function(result) {
                        select.prop('disabled', false);
                        select.addClass('is-valid');
                        setTimeout(() => select.removeClass('is-valid'), 2000);
                    },
                    error: function(err) {
                        alert("Error updating clock location");
                        select.prop('disabled', false);
                        select.addClass('is-invalid');
                    }
                });
            });
        });
    })();
</script>
@endif
