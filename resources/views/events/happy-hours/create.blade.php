@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{ __('Add Happy Hour') }} - {{ $event->name }}</h2>
    <livewire:edit-happy-hour :event="$event" :isManagerUpdating="$avUpdating" />
</div>
@endsection
