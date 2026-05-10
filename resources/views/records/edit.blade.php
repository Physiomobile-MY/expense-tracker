@extends('layouts.app')

@section('content')
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm font-semibold text-[#D71920]">Review Receipt Details</p>
        <h1 class="text-2xl font-bold text-gray-950">{{ $record->claim_reference_no ?: 'Draft Receipt' }}</h1>
    </div>
    <a href="{{ route('records.show', $record) }}" class="pm-btn-secondary">Record Detail</a>
</div>

@if ($record->duplicate_warning)
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
        Possible duplicate receipt detected. Please review before submitting.
    </div>
@endif

@include('records._form')

@include('records._void-form')
@endsection
