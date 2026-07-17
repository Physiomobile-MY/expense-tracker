@extends('layouts.app')

@section('content')
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm font-semibold text-[#D71920]">Reports</p>
        <h1 class="text-2xl font-bold text-gray-950">Export Centre</h1>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('reports.export', request()->query() + ['format' => 'csv']) }}" class="pm-btn-secondary">CSV</a>
        <a href="{{ route('reports.export', request()->query() + ['format' => 'xlsx']) }}" class="pm-btn-secondary">Excel</a>
        <a href="{{ route('reports.export', request()->query() + ['format' => 'pdf']) }}" class="pm-btn-primary">PDF</a>
    </div>
</div>

<section class="pm-card mb-4 p-4">
    <form method="GET" action="{{ route('reports.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label class="pm-label" for="date_from">From</label>
            <input class="pm-input" id="date_from" name="date_from" type="date" value="{{ request('date_from') }}">
        </div>
        <div>
            <label class="pm-label" for="date_to">To</label>
            <input class="pm-input" id="date_to" name="date_to" type="date" value="{{ request('date_to') }}">
        </div>
        <div>
            <label class="pm-label" for="staff_id">Staff / User</label>
            <select class="pm-input" id="staff_id" name="staff_id">
                <option value="">All</option>
                @foreach ($staff as $member)
                    <option value="{{ $member->id }}" @selected((string) request('staff_id') === (string) $member->id)>{{ $member->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="pm-label" for="department_id">Department</label>
            <select class="pm-input" id="department_id" name="department_id">
                <option value="">All</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="pm-label" for="expense_category_id">Category</label>
            <select class="pm-input" id="expense_category_id" name="expense_category_id">
                <option value="">All</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('expense_category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
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
            <input class="pm-input" id="status" name="status" value="{{ request('status') }}">
        </div>
        <div>
            <label class="pm-label" for="payment_method">Payment method</label>
            <input class="pm-input" id="payment_method" name="payment_method" value="{{ request('payment_method') }}">
        </div>
        <div class="flex items-end gap-2 lg:col-span-4">
            <button class="pm-btn-primary" type="submit">Filter</button>
            <a href="{{ route('reports.index') }}" class="pm-btn-secondary">Clear</a>
        </div>
    </form>
</section>

<div class="mb-4 grid gap-3 sm:grid-cols-4">
    <div class="pm-card p-4">
        <p class="text-xs font-semibold uppercase text-gray-500">Records</p>
        <p class="mt-2 text-2xl font-bold text-gray-950">{{ number_format($summary['count']) }}</p>
    </div>
    <div class="pm-card p-4">
        <p class="text-xs font-semibold uppercase text-gray-500">Total</p>
        <p class="mt-2 text-2xl font-bold text-gray-950">MYR {{ number_format((float) $summary['amount'], 2) }}</p>
    </div>
    <div class="pm-card p-4">
        <p class="text-xs font-semibold uppercase text-gray-500">Claimable</p>
        <p class="mt-2 text-2xl font-bold text-gray-950">MYR {{ number_format((float) $summary['claimable'], 2) }}</p>
    </div>
    <div class="pm-card p-4">
        <p class="text-xs font-semibold uppercase text-gray-500">Non-Claimable</p>
        <p class="mt-2 text-2xl font-bold text-gray-950">MYR {{ number_format((float) $summary['non_claimable'], 2) }}</p>
    </div>
</div>

<form method="POST" action="{{ route('reports.bulk-status') }}">
    @csrf
    @method('PATCH')
    <section class="pm-card overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-gray-100 bg-gray-50 p-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label class="pm-label" for="bulk_status">Change selected status</label>
                <select class="pm-input" id="bulk_status" name="status" required>
                    <option value="">Choose status</option>
                    @foreach ($bulkStatuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                @error('record_ids')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button class="pm-btn-primary" type="submit">Update Selected</button>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse ($records as $record)
                <div class="flex items-stretch">
                    <label class="flex cursor-pointer items-center px-4" title="Select {{ $record->claim_reference_no ?: 'record #'.$record->id }}">
                        <input
                            class="size-4 rounded border-gray-300 text-red-600 focus:ring-red-500"
                            type="checkbox"
                            name="record_ids[]"
                            value="{{ $record->id }}"
                            aria-label="Select {{ $record->claim_reference_no ?: 'record #'.$record->id }}"
                        >
                    </label>
                    <a href="{{ route('records.show', $record) }}" class="block flex-1 px-4 py-4 hover:bg-gray-50">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="font-bold text-gray-950">{{ $record->claim_reference_no ?: 'Draft Receipt' }}</p>
                                <p class="mt-1 truncate text-sm text-gray-600">{{ $record->merchant_name ?: '-' }} · {{ $record->user?->name }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $record->department?->name ?: 'No Department' }} · {{ $record->claimExpenseTypeLabel() }} · {{ $record->category?->name ?: 'No Category' }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="font-bold text-gray-950">MYR {{ number_format((float) $record->total_amount, 2) }}</p>
                                @include('partials.status-badge', ['status' => $record->status, 'label' => $record->statusLabel()])
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="px-4 py-10 text-center text-sm text-gray-500">No records match the filters.</div>
            @endforelse
        </div>
        <div class="border-t border-gray-100 px-4 py-3">
            {{ $records->links() }}
        </div>
    </section>
</form>
@endsection
