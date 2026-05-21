@extends('layouts.app')

@section('content')
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm font-semibold text-[#D71920]">{{ auth()->user()->canManageExpenses() ? 'Expense Records' : 'My Records' }}</p>
        <h1 class="text-2xl font-bold text-gray-950">Receipts and Claims</h1>
    </div>
    <a href="{{ route('receipts.create') }}" class="pm-btn-primary">Upload Claim</a>
</div>

<section class="pm-card mb-4 p-4">
    <form method="GET" action="{{ route('records.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <div>
            <label class="pm-label" for="record_type">Type</label>
            <select class="pm-input" id="record_type" name="record_type">
                <option value="">All</option>
                <option value="claimable" @selected(request('record_type') === 'claimable')>Claimable</option>
                <option value="non_claimable" @selected(request('record_type') === 'non_claimable')>Non-Claimable</option>
            </select>
        </div>
        <div>
            <label class="pm-label" for="status">Status</label>
            <select class="pm-input" id="status" name="status">
                <option value="">All</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @if (auth()->user()->canManageExpenses())
            <div>
                <label class="pm-label" for="department_id">Department</label>
                <select class="pm-input" id="department_id" name="department_id">
                    <option value="">All</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div>
            <label class="pm-label" for="date_from">From</label>
            <input class="pm-input" id="date_from" name="date_from" type="date" value="{{ request('date_from') }}">
        </div>
        <div>
            <label class="pm-label" for="date_to">To</label>
            <input class="pm-input" id="date_to" name="date_to" type="date" value="{{ request('date_to') }}">
        </div>
        <div class="flex items-end gap-2">
            <button class="pm-btn-primary flex-1" type="submit">Filter</button>
            <a href="{{ route('records.index') }}" class="pm-btn-secondary">Clear</a>
        </div>
    </form>
</section>

<section class="pm-card overflow-hidden">
    <div class="divide-y divide-gray-100">
        @forelse ($records as $record)
            <a href="{{ route('records.show', $record) }}" class="block px-4 py-4 hover:bg-gray-50">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-bold text-gray-950">{{ $record->claim_reference_no ?: 'Draft Receipt' }}</p>
                            @include('partials.status-badge', ['status' => $record->status, 'label' => $record->statusLabel()])
                            @if ($record->duplicate_warning)
                                <span class="pm-badge bg-red-50 text-red-700">Duplicate</span>
                            @endif
                        </div>
                        <p class="mt-1 truncate text-sm text-gray-600">{{ $record->merchant_name ?: 'Merchant not entered' }}</p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ $record->recordTypeLabel() }} · {{ $record->claimExpenseTypeLabel() }} · {{ $record->category?->name ?: 'No category' }}
                            @if ($record->route_distance_km)
                                · {{ number_format((float) $record->route_distance_km, 2) }} km
                            @endif
                            @if (auth()->user()->canManageExpenses())
                                · {{ $record->user?->name }}
                            @endif
                        </p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="font-bold text-gray-950">MYR {{ number_format((float) $record->total_amount, 2) }}</p>
                        <p class="text-xs text-gray-500">{{ $record->receipt_date?->format('Y-m-d') ?: $record->created_at->format('Y-m-d') }}</p>
                    </div>
                </div>
            </a>
        @empty
            <div class="px-4 py-10 text-center text-sm text-gray-500">No expense records found.</div>
        @endforelse
    </div>
    <div class="border-t border-gray-100 px-4 py-3">
        {{ $records->links() }}
    </div>
</section>
@endsection
