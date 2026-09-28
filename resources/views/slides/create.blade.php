@extends('layouts.app')

@section('content')

<div class="container">
    <h2>{{ __('New Slide') }}</h2>
    <div class="row justify-content-center">
        <livewire:edit-slide :$type />
    </div>
</div>
@endsection
