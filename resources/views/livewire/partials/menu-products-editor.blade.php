@error('products')
<div class="alert alert-danger">{{ $message }}</div>
@enderror

@foreach ($form->products as $i => $product)
<div class="card mb-2" wire:key="product-{{ $i }}">
    <div class="card-body">
        <div class="row g-2 align-items-start">
            <div class="col-md-10">
                <x-forms.inputs.text name="form.products.{{ $i }}.name" label="{{ __('Name') }}" required />
            </div>
            <div class="col-md-2 d-flex justify-content-end">
                <button type="button" class="btn btn-outline-danger mt-4" wire:click="removeProduct({{ $i }})">
                    <i class="fas fa-fw fa-trash-can"></i> {{ __('Remove') }}
                </button>
            </div>
        </div>

        <div class="row g-2 mt-1">
            <div class="col-md-3">
                <x-forms.inputs.text name="form.products.{{ $i }}.size" label="{{ __('Size') }}" />
            </div>
            <div class="col-md-3">
                <x-forms.inputs.text name="form.products.{{ $i }}.price" label="{{ __('Price') }}" required />
            </div>
            <div class="col-md-3">
                <x-forms.inputs.text name="form.products.{{ $i }}.size2" label="{{ __('Second size (optional)') }}" />
            </div>
            <div class="col-md-3">
                <x-forms.inputs.text name="form.products.{{ $i }}.price2" label="{{ __('Second price (optional)') }}" />
            </div>
        </div>

        <div class="row g-2">
            <div class="col-auto">
                <x-forms.inputs.checkbox name="form.products.{{ $i }}.special" label="{{ __('Highlight as special') }}" />
            </div>
            <div class="col-auto">
                <x-forms.inputs.checkbox name="form.products.{{ $i }}.disabled" label="{{ __('Disabled') }}" />
            </div>
        </div>
    </div>
</div>
@endforeach

<button type="button" class="btn btn-primary mb-3" wire:click="addProduct">
    <i class="fas fa-plus"></i> {{ __('Add product') }}
</button>
