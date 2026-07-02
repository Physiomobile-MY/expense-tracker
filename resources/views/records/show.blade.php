@extends('layouts.app')

@section('content')
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm font-semibold text-[#D71920]">{{ $record->recordTypeLabel() }}</p>
        @php $headerReceipt = $record->receipts->first(); @endphp
        <h1 class="text-2xl font-bold text-gray-950">{{ $record->claim_reference_no ?: ($headerReceipt?->isRouteScreenshot() ? 'Draft Route Claim' : 'Draft Receipt') }}</h1>
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
    @php
        $isRouteScreenshot = $record->receipts->first()?->isRouteScreenshot();
    @endphp
    <section class="pm-card overflow-hidden">
        <div class="border-b border-gray-100 px-4 py-3 flex items-center gap-2">
            <h2 class="font-bold text-gray-950">{{ $isRouteScreenshot ? 'Route Files' : 'Receipts' }}</h2>
            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600">{{ $record->receipts->count() }}</span>
        </div>

        {{-- Primary preview --}}
        @php $primary = $record->receipts->first(); @endphp
        @if ($primary?->isPreviewableImage())
            <div class="p-4 pb-2">
                <p class="mb-2 text-xs font-semibold uppercase text-gray-500">{{ $primary->documentTypeLabel() }}</p>
                <img src="{{ route('receipts.file', $primary) }}" alt="Receipt preview" class="max-h-[28rem] w-full rounded-lg border border-gray-200 object-contain cursor-pointer" id="primary-preview">
            </div>
        @elseif ($primary)
            <div class="m-4 rounded-lg border border-gray-200 bg-gray-50 p-4 text-center">
                <p class="font-semibold text-gray-900 text-sm">{{ $primary->original_filename }}</p>
                @if ($primary->isHeic())
                    <p class="mt-1 text-xs text-gray-500">HEIC preview not supported in this browser.</p>
                @endif
                <a href="{{ route('receipts.file', $primary) }}" class="mt-2 inline-flex text-sm font-semibold text-[#D71920]" target="_blank">Open File</a>
            </div>
        @else
            <div class="px-4 py-8 text-center text-sm text-gray-500">No receipt files.</div>
        @endif

        {{-- All receipts strip --}}
        @if ($record->receipts->count() > 1)
            <div class="border-t border-gray-100 p-4">
                <p class="mb-3 text-xs font-semibold uppercase text-gray-500">All Attachments</p>
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($record->receipts as $receipt)
                        <a href="{{ route('receipts.file', $receipt) }}" target="_blank" class="group relative block overflow-hidden rounded-lg border border-gray-200 bg-gray-50" title="{{ $receipt->original_filename }}">
                            @if ($receipt->isPreviewableImage())
                                <img src="{{ route('receipts.file', $receipt) }}" alt="" class="aspect-square w-full object-cover transition group-hover:opacity-80">
                            @else
                                <div class="flex aspect-square w-full items-center justify-center text-sm font-bold text-gray-400 uppercase">
                                    {{ $receipt->isPdf() ? 'PDF' : pathinfo($receipt->original_filename, PATHINFO_EXTENSION) }}
                                </div>
                            @endif
                            <div class="absolute bottom-0 left-0 right-0 bg-black/50 px-1.5 py-1">
                                <p class="truncate text-[10px] font-semibold text-white">{{ $receipt->documentTypeLabel() }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    <section class="pm-card overflow-hidden {{ $isRouteScreenshot ? 'lg:col-span-2' : '' }}">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="font-bold text-gray-950">Details</h2>
        </div>
        <dl class="grid gap-px bg-gray-100 text-sm sm:grid-cols-2">
            @php
                $detailRows = [
                    'Staff' => $record->user?->name,
                    'Department' => $record->department?->name,
                ];

                if ($isRouteScreenshot) {
                    $detailRows['Route Source'] = $record->routeSourceName();
                    $detailRows['Journey Date'] = $record->receipt_date?->format('Y-m-d');
                    $detailRows['Journey Time'] = $record->receipt_time ? (is_string($record->receipt_time) ? substr($record->receipt_time, 0, 5) : $record->receipt_time->format('H:i')) : null;
                } else {
                    $detailRows['Merchant'] = $record->merchant_name;
                    $detailRows['Receipt Date'] = $record->receipt_date?->format('Y-m-d');
                    $detailRows['Receipt Time'] = $record->receipt_time ? (is_string($record->receipt_time) ? substr($record->receipt_time, 0, 5) : $record->receipt_time->format('H:i')) : null;
                    $detailRows['Receipt No'] = $record->receipt_number;
                    $detailRows['Payment Method'] = $record->payment_method;
                }

                $detailRows = array_merge($detailRows, [
                    'Claim Type' => $record->claimExpenseTypeLabel(),
                    'Category' => $record->category?->name,
                    'Project / Cost Center' => $record->project_cost_center,
                    'Total Amount' => 'MYR '.number_format((float) $record->total_amount, 2),
                ]);
            @endphp
            @foreach ($detailRows as $label => $value)
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

@if ($record->hasTravelClaimDetails())
    <section class="pm-card mt-4 overflow-hidden">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="font-bold text-gray-950">Mileage, Toll & Parking</h2>
        </div>
        <dl class="grid gap-px bg-gray-100 text-sm sm:grid-cols-4">
            @foreach ([
                'From' => $record->route_origin,
                'To' => $record->route_destination,
                'Route / Via' => $record->route_summary,
                'ETA' => $record->route_arrival_time,
                'Distance' => $record->route_distance_km ? number_format((float) $record->route_distance_km, 2).' km' : null,
                'Duration' => $record->route_duration_minutes ? $record->route_duration_minutes.' min' : null,
                'Mileage Rate' => $record->mileage_rate ? 'MYR '.number_format((float) $record->mileage_rate, 2).' / km' : null,
                'Mileage Amount' => $record->mileage_amount ? 'MYR '.number_format((float) $record->mileage_amount, 2) : null,
                'Toll' => $record->toll_amount ? 'MYR '.number_format((float) $record->toll_amount, 2) : null,
                'Parking' => $record->parking_amount ? 'MYR '.number_format((float) $record->parking_amount, 2) : null,
            ] as $label => $value)
                <div class="bg-white p-4">
                    <dt class="text-xs font-semibold uppercase text-gray-500">{{ $label }}</dt>
                    <dd class="mt-1 font-medium text-gray-950">{{ $value ?: '-' }}</dd>
                </div>
            @endforeach
        </dl>
        @if (filled($record->toll_entries))
            <div class="border-t border-gray-100 bg-white p-4">
                <h3 class="text-sm font-bold text-gray-950">Toll Breakdown</h3>
                <div class="mt-3 divide-y divide-gray-100 rounded-lg border border-gray-100">
                    @foreach ($record->toll_entries as $entry)
                        <div class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                            <span class="text-gray-600">{{ ($entry['label'] ?? null) ?: 'Toll' }}</span>
                            <span class="font-semibold text-gray-950">MYR {{ number_format((float) $entry['amount'], 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
@endif

@if ($record->hasMedicalDetails())
    <section class="pm-card mt-4 overflow-hidden">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="font-bold text-gray-950">Medical Claim Details</h2>
        </div>
        <dl class="grid gap-px bg-gray-100 text-sm sm:grid-cols-3">
            @foreach ([
                'Patient Name' => $record->medical_patient_name,
                'Relationship' => $record->medical_relationship ? str($record->medical_relationship)->headline()->toString() : null,
                'Panel Clinic' => $record->medical_panel_clinic !== null ? ($record->medical_panel_clinic ? 'Yes' : 'No') : null,
                'Doctor' => $record->medical_doctor_name,
                'Diagnosis / Reason' => $record->medical_diagnosis,
                'Consultation Fee' => $record->medical_consultation_fee ? 'MYR '.number_format((float) $record->medical_consultation_fee, 2) : null,
                'Medication Fee' => $record->medical_medication_fee ? 'MYR '.number_format((float) $record->medical_medication_fee, 2) : null,
            ] as $label => $value)
                <div class="bg-white p-4">
                    <dt class="text-xs font-semibold uppercase text-gray-500">{{ $label }}</dt>
                    <dd class="mt-1 font-medium text-gray-950">{{ $value ?? '-' }}</dd>
                </div>
            @endforeach
        </dl>
    </section>
@endif

@if ($record->hasHotelDetails())
    <section class="pm-card mt-4 overflow-hidden">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="font-bold text-gray-950">Hotel Details</h2>
        </div>
        <dl class="grid gap-px bg-gray-100 text-sm sm:grid-cols-3">
            @foreach ([
                'Check-in Date' => $record->hotel_check_in_date?->format('d M Y'),
                'Check-out Date' => $record->hotel_check_out_date?->format('d M Y'),
                'Nights' => $record->hotel_num_nights,
                'Check-in Time' => $record->hotel_check_in_time,
                'Check-out Time' => $record->hotel_check_out_time,
                'Room Number' => $record->hotel_room_number,
                'Room Type' => $record->hotel_room_type,
                'Adults' => $record->hotel_num_adults,
                'Children' => $record->hotel_num_children,
            ] as $label => $value)
                <div class="bg-white p-4">
                    <dt class="text-xs font-semibold uppercase text-gray-500">{{ $label }}</dt>
                    <dd class="mt-1 font-medium text-gray-950">{{ $value ?? '-' }}</dd>
                </div>
            @endforeach
        </dl>
    </section>
@endif

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
    @unless ($isRouteScreenshot)
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
    @endunless

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
