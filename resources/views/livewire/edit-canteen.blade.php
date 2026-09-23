<div class="col-md-12">
    <div class="fixed-top loader-line" wire:loading.delay></div>
    <x-forms.helpers.banner :bannerMessage="$form->errorMessage" :level="$form->level" />

    <form wire:submit="@if ($action=='edit') updateCanteen @else createCanteen @endif">
        <x-forms.inputs.text name="form.name" label="{{ __('Name') }}" placeholder="{{ __('Mensa Ilmenau') }}" required />

        <x-forms.inputs.number name="form.external_id" label="{{ __('Canteen ID') }}" required>
            <x-forms.helpers.help text="{{ __('The resources_id used by the Studierendenwerk Speiseplan (stw-thueringen.de), e.g. from the dropdown at stw-thueringen.de/mensen/.') }}" />
        </x-forms.inputs.number>

        <x-forms.inputs.number name="form.external_id_en" label="{{ __('English Canteen ID') }}">
            <x-forms.helpers.help text="{{ __('Optional: the resources_id from the English site edition (stw-thueringen.de/en/dining-halls/), used to show the menu in English. Leave empty to always show German.') }}" />
        </x-forms.inputs.number>

        <x-forms.inputs.time name="form.start_time" label="{{ __('Start Time') }}" required />
        <x-forms.inputs.time name="form.end_time" label="{{ __('End Time') }}" required />

        <div x-data="{ isDeleting: false }">
            <template x-if="!isDeleting">
                <livewire:recurrence-editor wire:model.live="form.rrule" :exception-dates="$form->exceptionDates" />
            </template>

            <x-forms.inputs.checkbox name="form.disabled" label="{{ __('Disabled') }}">
                <x-forms.helpers.help text="{{ __('When checked, the canteen menu is not displayed') }}" />
            </x-forms.inputs.checkbox>

            <x-forms.inputs.select name="form.monitor_ids" label="{{ __('Monitors') }}" multiple>
                <x-slot:options>
                    @forelse ($allMonitors as $monitor)
                        <option value="{{ $monitor->id }}">{{ $monitor->name }}</option>
                    @empty
                        <option disabled>{{ __('No monitors available') }}</option>
                    @endforelse
                </x-slot>
                <x-forms.helpers.help text="{{ __('This canteen is not shown anywhere until at least one monitor is selected here.') }}" />
            </x-forms.inputs.select>

            <div class="my-3"></div>

            <x-forms.buttons.primary text="{{ __('Save Canteen') }}" />
            @if ($action <> 'create')
                <x-forms.buttons.delete x-on:click.prevent="if (confirm('{{ __('Are you sure?') }}')) { isDeleting = true; $wire.deleteCanteen() }" text="{{ __('Delete Canteen') }}" />
            @endif
        </div>
    </form>
</div>
