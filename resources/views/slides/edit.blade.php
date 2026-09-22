@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{ __('Edit Slide') }}</h2>
    <div class="row justify-content-center">
        <livewire:edit-slide :$slide />
    </div>
</div>
@endsection
