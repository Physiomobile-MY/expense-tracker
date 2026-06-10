@extends('layouts.app')

@section('content')
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div><p class="text-sm font-semibold text-[#D71920]">Every movement recorded</p><h1 class="text-2xl font-bold text-gray-950">Transaction Ledger</h1></div>
    <form class="flex gap-2"><input class="pm-input" name="search" value="{{ request('search') }}" placeholder="Search reference"><select class="pm-input" name="type"><option value="">All</option><option value="inflow" @selected(request('type')==='inflow')>Inflow</option><option value="outflow" @selected(request('type')==='outflow')>Outflow</option></select><button class="pm-btn-secondary">Filter</button></form>
</div>
@if (auth()->user()->canManageCcc())
<form method="POST" action="{{ route('ccc.transactions.store') }}" class="pm-card mb-5 grid gap-3 p-4 md:grid-cols-4">
    @csrf
    <div><label class="pm-label">Date</label><input class="pm-input" type="date" name="date" value="{{ now()->format('Y-m-d') }}" required></div>
    <div><label class="pm-label">Type</label><select class="pm-input" name="type" required><option value="inflow">Inflow</option><option value="outflow">Outflow</option></select></div>
    <div><label class="pm-label">Category</label><select class="pm-input" name="transaction_category_id"><option value="">Uncategorised</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ str($category->type)->headline() }} · {{ $category->name }}</option>@endforeach</select></div>
    <div><label class="pm-label">Amount</label><input class="pm-input" type="number" step="0.01" name="amount" required></div>
    <div><label class="pm-label">Method</label><input class="pm-input" name="payment_method"></div>
    <div><label class="pm-label">Reference</label><input class="pm-input" name="reference_number"></div>
    <div><label class="pm-label">Creditor</label><select class="pm-input" name="creditor_id"><option value="">None</option>@foreach($creditors as $creditor)<option value="{{ $creditor->id }}">{{ $creditor->creditor_name }}</option>@endforeach</select></div>
    <div><label class="pm-label">Debt</label><select class="pm-input" name="creditor_debt_id"><option value="">None</option>@foreach($debts as $debt)<option value="{{ $debt->id }}">{{ $debt->creditor->creditor_name }} · {{ $debt->invoice_number ?: '#'.$debt->id }}</option>@endforeach</select></div>
    <div class="md:col-span-3"><label class="pm-label">Description</label><input class="pm-input" name="description"></div>
    <div class="flex items-end"><button class="pm-btn-primary w-full">Record</button></div>
</form>
@endif
<section class="pm-card overflow-hidden"><table class="w-full text-left text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Date</th><th>Type</th><th>Category</th><th>Reference</th><th>Creditor</th><th class="text-right">Amount</th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($items as $item)<tr><td class="px-4 py-3">{{ $item->date->format('d M Y') }}</td><td>{{ str($item->type)->headline() }}</td><td>{{ $item->category?->name ?: 'Uncategorised' }}</td><td>{{ $item->reference_number }}</td><td>{{ $item->creditor?->creditor_name }}</td><td class="px-4 py-3 text-right font-bold">RM {{ number_format((float) $item->amount, 2) }}</td></tr>@empty<tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No transactions yet.</td></tr>@endforelse</tbody></table><div class="p-4">{{ $items->links() }}</div></section>
@endsection
