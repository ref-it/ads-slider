<div class="col-md-9">
    <div class="fixed-top loader-line" wire:loading.delay></div>
    <x-forms.helpers.banner :bannerMessage="$form->errorMessage" :level="$form->level" />

    <form wire:submit="@if ($action=='edit') updateSlide @else createSlide @endif">
        <x-forms.inputs.time name="form.start_time" label="{{ __('Start Time') }}" />
        <x-forms.inputs.time name="form.end_time" label="{{ __('End Time') }}" />

        <x-forms.inputs.date name="form.start" label="{{ __('Start') }}" />
        <x-forms.inputs.date name="form.end" label="{{ __('End') }}" />

        <div x-data="{ isDeleting: false }">
            <template x-if="!isDeleting">
                <livewire:weekdays wire:model="form.repeat" name="form.repeat" label="{{ __('Repeat on…') }}" />
            </template>

            <x-forms.inputs.checkbox name="form.disabled" label="{{ __('Disabled') }}">
                <x-forms.helpers.help text="{{ __('When checked, the slide is not displayed') }}" />
            </x-forms.inputs.checkbox>

            @if ($action === 'create')
            <div class="mb-3">
                <label class="form-label d-block">{{ __('Slide type') }}</label>
                <div class="btn-group" role="group">
                    <input type="radio" class="btn-check" id="media_type_picture" value="picture" wire:model.live="form.media_type">
                    <label class="btn btn-outline-primary" for="media_type_picture"><i class="fa-solid fa-image"></i>&nbsp;{{ __('Picture') }}</label>

                    <input type="radio" class="btn-check" id="media_type_video" value="video" wire:model.live="form.media_type">
                    <label class="btn btn-outline-primary" for="media_type_video"><i class="fa-solid fa-film"></i>&nbsp;{{ __('Video') }}</label>
                </div>
            </div>
            @endif

            <x-forms.inputs.text name="form.media_name" label="{{ __('Name') }}" />

            @if ($action === 'create')
            <x-forms.inputs.upload name="form.media_upload" label="{{ $form->media_type === 'picture' ? __('Upload Picture') : __('Upload Video') }}" accept="{{ $form->media_type === 'picture' ? 'image/*' : 'video/mp4' }}" />
            @endif

            @if ($form->media_type === 'picture')
            <x-forms.inputs.number name="form.media_duration" label="{{ __('Duration (seconds)') }}" min="1" max="120" />
            @endif

            @if ($action === 'edit')
                <x-forms.inputs.color name="form.media_bg_color" label="{{ __('Background Color') }}" />
                <x-forms.inputs.color name="form.media_color" label="{{ __('Text Color') }}" />

                @if ($form->media_type === 'video')
                <x-forms.inputs.select name="form.media_clock_location" label="{{ __('Clock position') }}">
                    <x-slot:options>
                        <option value="0">{{ __('Disabled') }}</option>
                        <option value="1">↖ {{ __('Top left') }}</option>
                        <option value="2">↑ {{ __('Top middle') }}</option>
                        <option value="3">↗ {{ __('Top right') }}</option>
                        <option value="4">→ {{ __('Right middle') }}</option>
                        <option value="5">↘ {{ __('Bottom right') }} ({{ __('default') }})</option>
                        <option value="6">↓ {{ __('Bottom middle') }}</option>
                        <option value="7">↙ {{ __('Bottom left') }}</option>
                        <option value="8">← {{ __('Left middle') }}</option>
                        <option value="9">⊚ {{ __('Center') }}</option>
                    </x-slot:options>
                </x-forms.inputs.select>

                <div class="img_padding mb-3" style="background-color: {{ $form->media_bg_color }}; color: {{ $form->media_color }};">
                    <video height="200px" class="preview" controls muted preload="metadata">
                        <source src="{{ route('videos.show', $form->media_id) }}" type="video/mp4">
                        {{ __('This browser does not support videos') }}
                    </video><br>
                    <span class="example-text">{{ __('Example Text') }}</span>
                </div>
                @endif
            @endif

            <div class="mb-3">
                <label for="media_monitors" class="form-label">{{ __('Show only on these monitors') }}</label>
                <select id="media_monitors" wire:model="form.media_monitors" class="form-control" multiple>
                    @foreach ($monitors as $m)
                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                </select>
                <small class="form-text">{{ __('Hint: do not select any monitor to show it on all monitors.') }}</small>
            </div>

            <div class="my-3"></div>

            <x-forms.buttons.primary text="{{ __('Save Slide') }}" />
            @if ($action !== 'create')
                <x-forms.buttons.delete x-on:click.prevent="if (confirm('{{ __('Are you sure?') }}')) { isDeleting = true; $wire.deleteSlide() }" text="{{ __('Delete Slide') }}" />
            @endif
        </div>
    </form>

    @if ($action === 'edit' && $this->currentPicture)
    <div wire:ignore wire:key="picture-sources-{{ $this->currentPicture->id }}">
        @include('livewire.partials.picture-sources', ['picture' => $this->currentPicture])
    </div>
    @endif
</div>

@if ($action === 'edit' && $form->media_type === 'picture')
<script>
    document.addEventListener('livewire:initialized', () => {
        const bgColor = document.getElementById('form.media_bg_color');
        const color = document.getElementById('form.media_color');
        bgColor?.addEventListener('input', (e) => {
            document.querySelectorAll('.preview_box').forEach((el) => el.style.backgroundColor = e.target.value);
        });
        color?.addEventListener('input', (e) => {
            document.querySelectorAll('.preview_box').forEach((el) => el.style.color = e.target.value);
        });
    });
</script>
@endif
