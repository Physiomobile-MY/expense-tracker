@extends('layouts.app')

@section('content')
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm font-semibold text-[#D71920]">{{ $record->recordTypeLabel() }}</p>
        <h1 class="text-2xl font-bold text-gray-950">{{ $record->claim_reference_no ?: 'Draft Receipt' }}</h1>
        <div class="mt-2 flex flex-wrap gap-2">
            @include('partials.status-badge', ['status' => $record->status, 'label' => $record->statusLabel()])
            @if ($record->duplicate_warning)
                <span class="pm-badge bg-red-50 text-red-700">Possible Duplicate</span>
            @endif
            @if ($record->ai_confidence_score !== null)
                <span class="pm-badge bg-gray-100 text-gray-700">AI {{ number_format((float) $record->ai_confidence_score * 100) }}%</span>
            @endif
        </div>
    </div>
    <div class="flex gap-2">
        @if ($record->canBeEditedBy(auth()->user()))
            <a href="{{ route('records.edit', $record) }}" class="pm-btn-primary">Edit</a>
        @endif
        <a href="{{ route('records.index') }}" class="pm-btn-secondary">Back</a>
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
    <section class="pm-card overflow-hidden">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="font-bold text-gray-950">Receipt</h2>
        </div>
        <div class="p-4">
            @php $receipt = $record->receipts->first(); @endphp
            @if ($receipt?->isImage())
                <img src="{{ route('receipts.file', $receipt) }}" alt="Receipt preview" class="max-h-[34rem] w-full rounded-lg border border-gray-200 object-contain">
            @elseif ($receipt)
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-5 text-center">
                    <p class="font-semibold text-gray-900">{{ $receipt->original_filename }}</p>
                    <a href="{{ route('receipts.file', $receipt) }}" class="mt-3 inline-flex text-sm font-semibold text-[#D71920]" target="_blank">Open PDF</a>
                </div>
            @else
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-5 text-center text-sm text-gray-500">No receipt file.</div>
            @endif
        </div>
    </section>

    <section class="pm-card overflow-hidden">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="font-bold text-gray-950">Details</h2>
        </div>
        <dl class="grid gap-px bg-gray-100 text-sm sm:grid-cols-2">
            @foreach ([
                'Staff' => $record->user?->name,
                'Department' => $record->department?->name,
                'Merchant' => $record->merchant_name,
                'Receipt Date' => $record->receipt_date?->format('Y-m-d'),
                'Receipt Time' => $record->receipt_time ? (is_string($record->receipt_time) ? substr($record->receipt_time, 0, 5) : $record->receipt_time->format('H:i')) : null,
                'Receipt No' => $record->receipt_number,
                'Category' => $record->category?->name,
                'Payment Method' => $record->payment_method,
                'Project / Cost Center' => $record->project_cost_center,
                'Total Amount' => 'MYR '.number_format((float) $record->total_amount, 2),
            ] as $label => $value)
                <div class="bg-white p-4">
                    <dt class="text-xs font-semibold uppercase text-gray-500">{{ $label }}</dt>
                    <dd class="mt-1 font-medium text-gray-950">{{ $value ?: '-' }}</dd>
                </div>
            @endforeach
            <div class="bg-white p-4 sm:col-span-2">
                <dt class="text-xs font-semibold uppercase text-gray-500">Purpose / Description</dt>
                <dd class="mt-1 whitespace-pre-line text-gray-950">{{ $record->description ?: '-' }}</dd>
            </div>
            <div class="bg-white p-4 sm:col-span-2">
                <dt class="text-xs font-semibold uppercase text-gray-500">Remarks</dt>
                <dd class="mt-1 whitespace-pre-line text-gray-950">{{ $record->remarks ?: '-' }}</dd>
            </div>
        </dl>
    </section>
</div>

@php
    $canReviewClaim = $record->record_type === 'claimable' && in_array($record->status, ['submitted', 'pending_review', 'need_clarification'], true);
    $canPayClaim = $record->record_type === 'claimable' && $record->status === 'approved';
    $canReviewNonClaimable = $record->record_type === 'non_claimable' && in_array($record->status, ['recorded', 'flagged'], true);
@endphp

@if (auth()->user()->canManageExpenses() && ($canReviewClaim || $canPayClaim || $canReviewNonClaimable))
    <section class="pm-card mt-4 p-4">
        <h2 class="font-bold text-gray-950">Review Actions</h2>
        <div class="mt-4 grid gap-3 lg:grid-cols-3">
            @if ($record->record_type === 'claimable')
                @if ($canReviewClaim)
                    <form method="POST" action="{{ route('records.approve', $record) }}" class="space-y-2">
                        @csrf
                        <textarea class="pm-input min-h-20" name="remarks" placeholder="Approval remarks"></textarea>
                        <button class="pm-btn-primary w-full" type="submit">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('records.reject', $record) }}" class="space-y-2">
                        @csrf
                        <textarea class="pm-input min-h-20" name="remarks" placeholder="Rejection remarks"></textarea>
                        <button class="pm-btn-secondary w-full" type="submit">Reject</button>
                    </form>
                    <form method="POST" action="{{ route('records.clarify', $record) }}" class="space-y-2">
                        @csrf
                        <textarea class="pm-input min-h-20" name="remarks" placeholder="Clarification needed" required></textarea>
                        <button class="pm-btn-secondary w-full" type="submit">Request Clarification</button>
                    </form>
                @endif
                @if ($record->status === 'approved')
                    <form method="POST" action="{{ route('records.paid', $record) }}" class="space-y-2 lg:col-span-3">
                        @csrf
                        <input class="pm-input" name="remarks" placeholder="Payment reference or notes">
                        <button class="pm-btn-primary w-full" type="submit">Mark as Paid</button>
                    </form>
                @endif
            @elseif ($canReviewNonClaimable)
                <form method="POST" action="{{ route('records.review', $record) }}" class="space-y-2">
                    @csrf
                    <textarea class="pm-input min-h-20" name="remarks" placeholder="Review remarks"></textarea>
                    <button class="pm-btn-primary w-full" type="submit">Mark Reviewed</button>
                </form>
                <form method="POST" action="{{ route('records.flag', $record) }}" class="space-y-2">
                    @csrf
                    <textarea class="pm-input min-h-20" name="remarks" placeholder="Flag remarks"></textarea>
                    <button class="pm-btn-secondary w-full" type="submit">Flag</button>
                </form>
            @endif
        </div>
    </section>
@endif

@include('records._void-form')

<div class="mt-4 grid gap-4 lg:grid-cols-2">
    <section class="pm-card overflow-hidden">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="font-bold text-gray-950">Items</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse ($record->items as $item)
                <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-3 px-4 py-3 text-sm">
                    <div>
                        <p class="font-medium text-gray-950">{{ $item->description ?: '-' }}</p>
                        <p class="text-gray-500">Qty {{ $item->quantity ?: '-' }} · Unit {{ $item->unit_price ?: '-' }}</p>
                    </div>
                    <p class="font-semibold text-gray-950">MYR {{ number_format((float) $item->amount, 2) }}</p>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-sm text-gray-500">No line items recorded.</div>
            @endforelse
        </div>
    </section>

    <section class="pm-card overflow-hidden">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="font-bold text-gray-950">Comments</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse ($record->comments as $comment)
                <div class="px-4 py-3 text-sm">
                    <p class="font-semibold text-gray-950">{{ $comment->user?->name }}</p>
                    <p class="mt-1 whitespace-pre-line text-gray-600">{{ $comment->comment }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ $comment->created_at->format('Y-m-d H:i') }}</p>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-sm text-gray-500">No comments yet.</div>
            @endforelse
        </div>
        <form method="POST" action="{{ route('records.comments.store', $record) }}" class="border-t border-gray-100 p-4">
            @csrf
            <label class="pm-label" for="comment">Add comment</label>
            <textarea class="pm-input min-h-20" id="comment" name="comment" required></textarea>
            <button class="pm-btn-primary mt-3 w-full" type="submit">Send</button>
        </form>
    </section>
</div>
@endsection
