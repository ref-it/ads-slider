<div>
    <h2>{{ __('Edit Menu') }}</h2>
    <x-forms.helpers.banner :bannerMessage="$form->errorMessage" :level="$form->level" />

    <form wire:submit="save">
        <div class="row g-2">
            <div class="col-md-6 col-lg-4">
                <x-forms.inputs.text name="form.name" placeholder="{{ __('Cold drinks') }}"
                    label="{{ __('Name') }}" required />
            </div>
            <div class="col-md-6 col-lg-4">
                <x-forms.inputs.icon-picker name="form.icon" placeholder="snowflake" label="{{ __('Icon') }}">
                    <x-forms.helpers.any>
                        {{ __('Pick an icon from here:') }}&nbsp;<a
                            href="https://fontawesome.com/search?m=free&s=solid" target="_blank">link</a>
                    </x-forms.helpers.any>
                </x-forms.inputs.icon-picker>
            </div>
            <div class="col-md-6 col-lg-4">
                <x-forms.inputs.text name="form.currency" placeholder="€" label="{{ __('Currency') }}" required />
            </div>
        </div>

        <div class="mb-3">
            <x-forms.helpers.label name="form.description" label="{{ __('Internal note') }}" />
            <textarea id="form.description" wire:model="form.description" class="form-control" rows="3"></textarea>
            <x-forms.helpers.help text="{{ __('For internal use only, never shown on a monitor.') }}" />
        </div>

        <x-forms.inputs.select name="form.monitors" label="{{ __('Monitors') }}" multiple>
            <x-slot:options>
                @forelse ($monitors as $m)
                <option value="{{ $m->id }}">{{ $m->name }}</option>
                @empty
                <option disabled>{{ __('No monitors available') }}</option>
                @endforelse
            </x-slot>
            <x-forms.helpers.help text="{{ __('Hint: do not select any monitor to show it on all monitors.') }}" />
        </x-forms.inputs.select>

        <h4 class="mt-4">{{ __('Products') }}</h4>
        @include('livewire.partials.menu-products-editor')

        <div class="mt-3">
            <x-forms.buttons.primary text="{{ __('Update Menu') }}" />
            <x-forms.buttons.delete wire:click.prevent="deleteMenu" text="{{ __('Delete Menu') }}" />
        </div>
    </form>
</div>
