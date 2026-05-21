@extends('layouts.app')

@section('content')
@php
    $value = $openai->value ?? [];
    $claimValue = $claims->value ?? [];
@endphp
<div class="mb-5">
    <p class="text-sm font-semibold text-[#D71920]">System Settings</p>
    <h1 class="text-2xl font-bold text-gray-950">ExpenseFlow Settings</h1>
</div>

<section class="pm-card p-5">
    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4">
        @csrf
        @method('PUT')
        <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-3">
            <input type="checkbox" name="enabled" value="1" class="rounded border-gray-300 text-[#D71920] focus:ring-[#D71920]" @checked($value['enabled'] ?? false)>
            <span>
                <span class="block font-semibold text-gray-950">Enable AI receipt extraction</span>
                <span class="block text-sm text-gray-500">Manual entry remains available when disabled or unavailable.</span>
            </span>
        </label>
        <div>
            <label class="pm-label" for="model">Model</label>
            <input class="pm-input" id="model" name="model" value="{{ old('model', $value['model'] ?? config('services.openai.receipt_model')) }}" required>
        </div>
        <div>
            <label class="pm-label" for="daily_scan_limit">Daily scan limit</label>
            <input class="pm-input" id="daily_scan_limit" name="daily_scan_limit" type="number" min="0" value="{{ old('daily_scan_limit', $value['daily_scan_limit'] ?? config('services.openai.daily_scan_limit')) }}" required>
        </div>
        <div class="border-t border-gray-100 pt-4">
            <label class="pm-label" for="mileage_rate">Mileage rate per km</label>
            <input class="pm-input" id="mileage_rate" name="mileage_rate" type="number" min="0" step="0.01" value="{{ old('mileage_rate', $claimValue['mileage_rate'] ?? config('expenseflow.mileage.default_rate')) }}" required>
        </div>
        <button class="pm-btn-primary" type="submit">Save Settings</button>
    </form>
</section>
@endsection
