@extends('layouts.app')

@section('content')
<div class="mb-5">
    <p class="text-sm font-semibold text-[#D71920]">Daily Records</p>
    <h1 class="text-2xl font-bold text-gray-950">Daily Cashflow</h1>
</div>

@if (auth()->user()->canManageCcc())
<form method="POST" action="{{ route('ccc.cashflow.store') }}" class="pm-card mb-5 grid gap-3 p-4 md:grid-cols-5">
    @csrf
    <div><label class="pm-label">Date</label><input class="pm-input" type="date" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required></div>
    <div><label class="pm-label">Opening</label><input class="pm-input" type="number" step="0.01" name="opening_balance" required></div>
    <div><label class="pm-label">Inflow</label><input class="pm-input" type="number" step="0.01" name="total_inflow" value="0" required></div>
    <div><label class="pm-label">Outflow</label><input class="pm-input" type="number" step="0.01" name="total_outflow" value="0" required></div>
    <div class="flex items-end"><button class="pm-btn-primary w-full">Save Day</button></div>
    <div class="md:col-span-5"><label class="pm-label">Notes</label><textarea class="pm-input" name="notes" rows="2"></textarea></div>
</form>
@endif

<section class="pm-card overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Date</th><th>Opening</th><th>Inflow</th><th>Outflow</th><th>Closing</th><th>Notes</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($items as $item)
                <tr><td class="px-4 py-3 font-semibold">{{ $item->date->format('d M Y') }}</td><td>RM {{ number_format((float) $item->opening_balance, 2) }}</td><td>RM {{ number_format((float) $item->total_inflow, 2) }}</td><td>RM {{ number_format((float) $item->total_outflow, 2) }}</td><td class="font-bold">RM {{ number_format((float) $item->closing_balance, 2) }}</td><td class="text-gray-500">{{ $item->notes }}</td></tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No cashflow days recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $items->links() }}</div>
</section>
@endsection
