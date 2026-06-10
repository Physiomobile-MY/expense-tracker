@extends('layouts.app')

@section('content')
<div class="mb-5"><p class="text-sm font-semibold text-[#D71920]">Future payments</p><h1 class="text-2xl font-bold text-gray-950">Payment Plans</h1></div>
@if (auth()->user()->canManageCcc())
<form method="POST" action="{{ route('ccc.payment-plans.store') }}" class="pm-card mb-5 grid gap-3 p-4 md:grid-cols-5">@csrf
    <div><label class="pm-label">Creditor</label><select class="pm-input" name="creditor_id" required>@foreach($creditors as $creditor)<option value="{{ $creditor->id }}">{{ $creditor->creditor_name }}</option>@endforeach</select></div>
    <div><label class="pm-label">Debt</label><select class="pm-input" name="creditor_debt_id"><option value="">General</option>@foreach($debts as $debt)<option value="{{ $debt->id }}">{{ $debt->creditor->creditor_name }} · {{ $debt->invoice_number ?: '#'.$debt->id }}</option>@endforeach</select></div>
    <div><label class="pm-label">Planned Date</label><input class="pm-input" type="date" name="planned_payment_date" required></div>
    <div><label class="pm-label">Amount</label><input class="pm-input" type="number" step="0.01" name="planned_amount" required></div>
    <div><label class="pm-label">Priority</label><select class="pm-input" name="priority"><option value="critical">Critical</option><option value="high">High</option><option value="normal" selected>Normal</option><option value="low">Low</option></select></div>
    <div class="md:col-span-4"><label class="pm-label">Notes</label><input class="pm-input" name="notes"></div><div class="flex items-end"><button class="pm-btn-primary w-full">Plan Payment</button></div>
</form>
@endif
<section class="pm-card overflow-hidden"><table class="w-full text-left text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Date</th><th>Creditor</th><th>Debt</th><th>Priority</th><th>Status</th><th class="text-right">Amount</th><th></th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($items as $item)<tr><td class="px-4 py-3">{{ $item->planned_payment_date->format('d M Y') }}</td><td class="font-semibold">{{ $item->creditor->creditor_name }}</td><td>{{ $item->debt?->invoice_number ?: 'General' }}</td><td>{{ str($item->priority)->headline() }}</td><td>{{ str($item->status)->headline() }}</td><td class="text-right font-bold">RM {{ number_format((float) $item->planned_amount, 2) }}</td><td class="px-4 py-3">@if(auth()->user()->canManageCcc() && $item->status === 'planned')<form method="POST" action="{{ route('ccc.payment-plans.paid', $item) }}" class="flex gap-2">@csrf<input type="hidden" name="actual_payment_date" value="{{ now()->format('Y-m-d') }}"><input type="hidden" name="actual_amount_paid" value="{{ $item->planned_amount }}"><button class="pm-btn-secondary !px-3 !py-1.5">Mark Paid</button></form>@endif</td></tr>@empty<tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">No payment plans yet.</td></tr>@endforelse</tbody></table><div class="p-4">{{ $items->links() }}</div></section>
@endsection
