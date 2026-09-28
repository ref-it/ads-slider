@props(['name', 'label' => null])
<label for="{{ $name }}" class="form-label">
    <x-forms.helpers.label :name="$name" :label="$label" />
</label> 
<div class="input-group mb-3">
    <i class="align-middle input-group-text fs-4 fas" wire:ignore id="{{ $name }}-addon"></i>
    <input id="{{ $name }}" wire:model="{{ $name }}" type="text"
        class="form-control icon-picker-input @error($name) is-invalid @enderror" data-icon-addon="{{ $name }}-addon"
        {{$attributes}}>
    @error($name)
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
    {{ $slot }}
</div>
