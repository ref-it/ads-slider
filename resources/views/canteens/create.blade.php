@extends('layouts.app')

@section('content')

<div class="container">
    <h2>{{ __('New Canteen') }}</h2>
    <div class="row justify-content-center">
        <livewire:edit-canteen action="create" />
    </div>
</div>
@endsection
