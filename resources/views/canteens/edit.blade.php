@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{ __('Edit Canteen') }}</h2>
    <div class="row justify-content-center">
        <livewire:edit-canteen action="edit" :$canteen />
    </div>
</div>
@endsection
