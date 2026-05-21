@extends('layouts.app')

@section('content')
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm font-semibold text-[#D71920]">{{ now()->format('F Y') }}</p>
        <h1 class="text-2xl font-bold text-gray-950">{{ $user->canManageExpenses() ? 'Expense Dashboard' : 'My Dashboard' }}</h1>
    </div>
    <div class="flex gap-2">
        @if (! $user->canManageExpenses())
            <a href="{{ route('receipts.create') }}" class="pm-btn-primary">Upload Claim</a>
            <a href="{{ route('records.index') }}" class="pm-btn-secondary">My Claims</a>
        @else
            <a href="{{ route('receipts.create') }}" class="pm-btn-secondary">Upload Claim</a>
            <a href="{{ route('records.index', ['status' => 'pending_review']) }}" class="pm-btn-primary">Review Claims</a>
            <a href="{{ route('reports.index') }}" class="pm-btn-secondary">Reports</a>
        @endif
    </div>
</div>

@php
    $cards = $user->canManageExpenses()
        ? [
            ['label' => 'Company Expenses', 'value' => $metrics['claimable_month'] + $metrics['non_claimable_month']],
            ['label' => 'Claimable', 'value' => $metrics['claimable_month']],
            ['label' => 'Non-Claimable', 'value' => $metrics['non_claimable_month']],
            ['label' => 'Pending Approval', 'value' => $metrics['pending_month']],
            ['label' => 'Approved', 'value' => $metrics['approved_month']],
            ['label' => 'Paid', 'value' => $metrics['paid_month']],
            ['label' => 'Rejected', 'value' => $metrics['rejected_month']],
            ['label' => 'Pending Claims', 'value' => $metrics['pending_count'], 'count' => true],
        ]
        : [
            ['label' => 'Submitted This Month', 'value' => $metrics['claimable_month']],
            ['label' => 'Approved Amount', 'value' => $metrics['approved_month']],
            ['label' => 'Pending Amount', 'value' => $metrics['pending_month']],
            ['label' => 'Rejected Amount', 'value' => $metrics['rejected_month']],
            ['label' => 'Non-Claimable Receipts', 'value' => $metrics['non_claimable_count'], 'count' => true],
        ];
@endphp

<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($cards as $card)
        <div class="pm-card p-4">
            <p class="text-xs font-semibold uppercase text-gray-500">{{ $card['label'] }}</p>
            <p class="mt-2 text-2xl font-bold text-gray-950">
                @if ($card['count'] ?? false)
                    {{ number_format($card['value']) }}
                @else
                    MYR {{ number_format((float) $card['value'], 2) }}
                @endif
            </p>
        </div>
    @endforeach
</div>

@if ($user->canManageExpenses())
    <div class="mt-5 grid gap-4 lg:grid-cols-3">
        <section class="pm-card p-4">
            <h2 class="font-bold text-gray-950">Top Categories</h2>
            <div class="mt-3 space-y-3">
                @forelse ($categoryTotals as $row)
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <span class="truncate text-gray-600">{{ $row->label }}</span>
                        <span class="font-semibold text-gray-950">MYR {{ number_format((float) $row->total, 2) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No category data yet.</p>
                @endforelse
            </div>
        </section>
        <section class="pm-card p-4">
            <h2 class="font-bold text-gray-950">Departments</h2>
            <div class="mt-3 space-y-3">
                @forelse ($departmentTotals as $row)
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <span class="truncate text-gray-600">{{ $row->label }}</span>
                        <span class="font-semibold text-gray-950">MYR {{ number_format((float) $row->total, 2) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No department data yet.</p>
                @endforelse
            </div>
        </section>
        <section class="pm-card p-4">
            <h2 class="font-bold text-gray-950">AI Usage</h2>
            <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-gray-500">Scans</p>
                    <p class="text-lg font-bold text-gray-950">{{ number_format($aiUsage['scans'] ?? 0) }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Failed</p>
                    <p class="text-lg font-bold text-gray-950">{{ number_format($aiUsage['failed'] ?? 0) }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Input Tokens</p>
                    <p class="text-lg font-bold text-gray-950">{{ number_format($aiUsage['input_tokens'] ?? 0) }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Output Tokens</p>
                    <p class="text-lg font-bold text-gray-950">{{ number_format($aiUsage['output_tokens'] ?? 0) }}</p>
                </div>
            </div>
        </section>
    </div>
@endif

<div class="mt-5 grid gap-4 {{ $user->canManageExpenses() ? 'lg:grid-cols-3' : '' }}">
    <section class="pm-card overflow-hidden {{ $user->canManageExpenses() ? 'lg:col-span-2' : '' }}">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="font-bold text-gray-950">Recent Receipts</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse ($recent as $record)
                <a href="{{ route('records.show', $record) }}" class="block px-4 py-3 hover:bg-gray-50">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-gray-950">{{ $record->merchant_name ?: 'Draft receipt' }}</p>
                            <p class="text-sm text-gray-500">{{ $record->claim_reference_no ?: 'No reference yet' }} · {{ $record->recordTypeLabel() }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="font-bold text-gray-950">MYR {{ number_format((float) $record->total_amount, 2) }}</p>
                            @include('partials.status-badge', ['status' => $record->status, 'label' => $record->statusLabel()])
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-4 py-8 text-center text-sm text-gray-500">No receipts yet.</div>
            @endforelse
        </div>
    </section>

    @if ($user->canManageExpenses())
        <section class="pm-card overflow-hidden">
            <div class="border-b border-gray-100 px-4 py-3">
                <h2 class="font-bold text-gray-950">High-Value Claims</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($highValueClaims as $record)
                    <a href="{{ route('records.show', $record) }}" class="block px-4 py-3 hover:bg-gray-50">
                        <p class="font-semibold text-gray-950">{{ $record->claim_reference_no }}</p>
                        <p class="text-sm text-gray-500">{{ $record->user?->name }} · MYR {{ number_format((float) $record->total_amount, 2) }}</p>
                    </a>
                @empty
                    <div class="px-4 py-8 text-center text-sm text-gray-500">No high-value claims.</div>
                @endforelse
            </div>
        </section>
    @endif
</div>
@endsection
