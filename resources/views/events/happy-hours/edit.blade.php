@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{ __('Edit Happy Hour') }} - {{ $event->name }}</h2>
    <livewire:edit-happy-hour :event="$event" :happyHour="$happyHour" :isManagerUpdating="$avUpdating" />
</div>
@endsection
