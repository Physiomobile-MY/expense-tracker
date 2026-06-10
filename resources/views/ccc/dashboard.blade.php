@extends('layouts.app')

@section('content')
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm font-semibold text-[#D71920]">{{ now()->format('l, d M Y') }}</p>
        <h1 class="text-2xl font-bold text-gray-950">Cashflow Command Centre</h1>
    </div>
    <div class="flex flex-wrap gap-2">
        <a class="pm-btn-secondary" href="{{ route('ccc.transactions') }}">Ledger</a>
        <a class="pm-btn-secondary" href="{{ route('ccc.soa') }}">SOA</a>
        <a class="pm-btn-primary" href="{{ route('ccc.bank-reconciliation') }}">Upload Bank CSV</a>
    </div>
</div>

@php
    $cards = [
        ['label' => 'Opening Balance Today', 'value' => $metrics['opening_balance']],
        ['label' => 'Total Inflow Today', 'value' => $metrics['inflow_today']],
        ['label' => 'Total Outflow Today', 'value' => $metrics['outflow_today']],
        ['label' => 'Closing Balance Today', 'value' => $metrics['closing_balance']],
        ['label' => 'Creditor Outstanding', 'value' => $metrics['total_outstanding']],
        ['label' => 'Overdue Amount', 'value' => $metrics['overdue_amount']],
        ['label' => 'Planned This Week', 'value' => $metrics['planned_week']],
        ['label' => 'Available To Settle', 'value' => $metrics['available_for_settlement']],
    ];
@endphp

<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($cards as $card)
        <div class="pm-card p-4">
            <p class="text-xs font-semibold uppercase text-gray-500">{{ $card['label'] }}</p>
            <p class="mt-2 text-2xl font-bold text-gray-950">RM {{ number_format((float) $card['value'], 2) }}</p>
        </div>
    @endforeach
</div>

<div class="mt-5 grid gap-4 lg:grid-cols-3">
    <section class="pm-card p-4">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-gray-950">Finance Health Score</h2>
            <span class="text-2xl font-bold text-[#D71920]">{{ $healthScore }}</span>
        </div>
        <div class="mt-4 space-y-2">
            @foreach ($healthChecks as $label => $passed)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">{{ $label }}</span>
                    <span class="pm-badge {{ $passed ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $passed ? 'Done' : 'Pending' }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="pm-card p-4 lg:col-span-2">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-bold text-gray-950">Smart Settlement Recommendation</h2>
            <div class="text-sm text-gray-600">Available RM {{ number_format($metrics['cash_available'], 2) }} - Reserve RM {{ number_format($metrics['minimum_reserve'], 2) }}</div>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase text-gray-500">
                    <tr><th class="py-2">Creditor</th><th>Invoice</th><th>Suggested</th><th>After Payment</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($recommendations as $row)
                        <tr>
                            <td class="py-2 font-semibold text-gray-950">{{ $row['creditor']->creditor_name }}</td>
                            <td>{{ $row['debt']->invoice_number ?: 'Debt #'.$row['debt']->id }}</td>
                            <td>RM {{ number_format($row['suggested_amount'], 2) }}</td>
                            <td>RM {{ number_format($row['expected_outstanding'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-gray-500">No settlement budget or unpaid debts.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="mt-5 grid gap-4 lg:grid-cols-3">
    <section class="pm-card p-4">
        <h2 class="font-bold text-gray-950">Daily Cash Balance Trend</h2>
        <div class="mt-4 space-y-3">
            @forelse ($dailyTrend as $day)
                @php $width = max(3, min(100, ($day->closing_balance / max(1, $dailyTrend->max('closing_balance'))) * 100)); @endphp
                <div>
                    <div class="flex justify-between text-xs text-gray-500"><span>{{ $day->date->format('d M') }}</span><span>RM {{ number_format((float) $day->closing_balance, 2) }}</span></div>
                    <div class="mt-1 h-2 rounded-full bg-gray-100"><div class="h-2 rounded-full bg-[#D71920]" style="width: {{ $width }}%"></div></div>
                </div>
            @empty
                <p class="text-sm text-gray-500">No cashflow records yet.</p>
            @endforelse
        </div>
    </section>

    <section class="pm-card p-4">
        <h2 class="font-bold text-gray-950">Monthly Inflow vs Outflow</h2>
        <div class="mt-4 space-y-3">
            @forelse ($monthlyFlow as $period => $flow)
                <div class="text-sm">
                    <p class="font-semibold text-gray-900">{{ $period }}</p>
                    <p class="text-gray-600">In RM {{ number_format((float) ($flow['inflow'] ?? 0), 2) }} · Out RM {{ number_format((float) ($flow['outflow'] ?? 0), 2) }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-500">No ledger records yet.</p>
            @endforelse
        </div>
    </section>

    <section class="pm-card p-4">
        <h2 class="font-bold text-gray-950">Aging Analysis</h2>
        <div class="mt-4 space-y-3">
            @foreach ($aging as $label => $amount)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">{{ $label }}</span>
                    <span class="font-semibold text-gray-950">RM {{ number_format((float) $amount, 2) }}</span>
                </div>
            @endforeach
        </div>
    </section>
</div>

<section class="pm-card mt-5 overflow-hidden">
    <div class="border-b border-gray-100 px-4 py-3">
        <h2 class="font-bold text-gray-950">Top Creditors</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Creditor</th><th>Priority</th><th>Status</th><th class="text-right">Outstanding</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($topCreditors as $creditor)
                    <tr><td class="px-4 py-3 font-semibold">{{ $creditor->creditor_name }}</td><td>{{ str($creditor->priority)->headline() }}</td><td>{{ str($creditor->status)->headline() }}</td><td class="px-4 py-3 text-right font-bold">RM {{ number_format((float) $creditor->current_outstanding, 2) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No creditors yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
