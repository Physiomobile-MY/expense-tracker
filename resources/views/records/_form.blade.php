@php
    $primaryReceipt = $record->receipts->first();
    $isRouteScreenshot = $primaryReceipt?->isRouteScreenshot();
    $timeValue = $record->receipt_time ? (is_string($record->receipt_time) ? substr($record->receipt_time, 0, 5) : $record->receipt_time->format('H:i')) : '';
    $items = old('items', $record->items->map(fn ($item) => [
        'description' => $item->description,
        'quantity' => $item->quantity,
        'unit_price' => $item->unit_price,
        'amount' => $item->amount,
    ])->toArray());
    $itemCount = max(count($items), 5);
    $tollEntries = old('toll_entries', $record->toll_entries ?: ($record->toll_amount ? [['label' => 'Toll', 'amount' => $record->toll_amount]] : [[]]));
    $tollEntryCount = max(count($tollEntries), 1);
    $canEdit = $record->canBeEditedBy(auth()->user());
@endphp

<form method="POST" action="{{ route('records.update', $record) }}" class="space-y-5">
    @csrf
    @method('PUT')
    <input type="hidden" name="intent" value="claimable">

    <div class="grid gap-4 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
        {{-- Left panel: All attached receipts --}}
        <section class="pm-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                <h2 class="font-bold text-gray-950">Receipts <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600">{{ $record->receipts->count() }}</span></h2>
            </div>

            {{-- AI alerts --}}
            @if ($record->ai_confidence_score !== null && $record->ai_confidence_score < 0.75)
                <div class="mx-4 mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                    Some details may be inaccurate (AI {{ number_format((float) $record->ai_confidence_score * 100) }}% confidence). Please double-check before submitting.
                </div>
            @endif
            @if ($record->aiLogs->last()?->status === 'failed')
                <div class="mx-4 mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                    Auto-read failed. Enter details manually.
                </div>
            @endif

            {{-- Receipt cards --}}
            <div class="divide-y divide-gray-100" id="receipts-list">
                @forelse ($record->receipts as $receipt)
                    <div class="flex gap-3 p-4" data-receipt-id="{{ $receipt->id }}">
                        {{-- Thumbnail / icon --}}
                        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                            @if ($receipt->isPreviewableImage())
                                <img src="{{ route('receipts.file', $receipt) }}" alt="preview" class="h-full w-full object-cover cursor-pointer" data-preview-src="{{ route('receipts.file', $receipt) }}" data-preview-name="{{ $receipt->original_filename }}">
                            @elseif ($receipt->isPdf())
                                <div class="flex h-full w-full items-center justify-center text-2xl text-gray-400">PDF</div>
                            @else
                                <div class="flex h-full w-full items-center justify-center text-xs font-semibold text-gray-400 uppercase">{{ pathinfo($receipt->original_filename, PATHINFO_EXTENSION) }}</div>
                            @endif
                        </div>

                        {{-- Info + controls --}}
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ $receipt->original_filename }}</p>
                            <p class="mt-0.5 text-xs text-gray-400">{{ number_format($receipt->file_size / 1024, 0) }} KB · {{ $receipt->created_at->format('d M Y H:i') }}</p>

                            {{-- Document type selector (inline PATCH) --}}
                            @if ($canEdit)
                                <div class="mt-2 flex items-center gap-2 max-sm:flex-col max-sm:items-stretch">
                                    <select class="pm-input py-1 text-xs" name="document_type" form="receipt-update-form-{{ $receipt->id }}" onchange="this.form.submit()">
                                        <option value="receipt" @selected($receipt->document_type === 'receipt')>Receipt</option>
                                        <option value="waze_screenshot" @selected($receipt->document_type === 'waze_screenshot')>Waze Screenshot</option>
                                        <option value="google_maps_screenshot" @selected($receipt->document_type === 'google_maps_screenshot')>Google Maps</option>
                                    </select>
                                    <a href="{{ route('receipts.file', $receipt) }}" target="_blank" class="shrink-0 text-xs font-semibold text-[#D71920]">Open</a>
                                </div>
                            @else
                                <p class="mt-1 text-xs text-gray-500">{{ $receipt->documentTypeLabel() }}</p>
                                <a href="{{ route('receipts.file', $receipt) }}" target="_blank" class="mt-1 inline-block text-xs font-semibold text-[#D71920]">Open</a>
                            @endif
                        </div>

                        {{-- Delete --}}
                        @if ($canEdit && $record->receipts->count() > 1)
                            <button type="submit" form="receipt-delete-form-{{ $receipt->id }}" class="mt-1 text-xs font-semibold text-gray-400 hover:text-red-600" title="Remove">✕</button>
                        @endif
                    </div>
                @empty
                    <div class="px-4 py-8 text-center text-sm text-gray-500">No receipt files attached.</div>
                @endforelse
            </div>

            {{-- Attach another receipt --}}
            @if ($canEdit)
                <div class="border-t border-gray-100">
                    <button type="button" class="flex w-full items-center gap-2 px-4 py-3 text-left text-sm font-semibold text-[#D71920] hover:bg-gray-50" data-toggle-attach>
                        <span>+ Attach another receipt</span>
                    </button>
                    <div class="hidden px-4 pb-4" id="attach-form">
                        <div class="space-y-3" data-attach-form>
                            <div>
                                <label class="pm-label" for="attach_document_type">Type</label>
                                <select class="pm-input" id="attach_document_type" name="document_type" form="receipt-attach-form">
                                    <option value="receipt">Receipt</option>
                                    <option value="waze_screenshot">Waze Screenshot</option>
                                    <option value="google_maps_screenshot">Google Maps Screenshot</option>
                                </select>
                            </div>
                            <div>
                                <label class="pm-label" for="attach_receipt">File</label>
                                <input class="pm-input file:mr-2 file:rounded file:border-0 file:bg-[#FDECEC] file:px-2 file:py-1 file:text-xs file:font-semibold file:text-[#A80F16]" id="attach_receipt" name="receipt" type="file" accept=".jpg,.jpeg,.png,.heic,.heif,.pdf,image/jpeg,image/png,image/heic,image/heif,application/pdf" form="receipt-attach-form" required>
                                <p class="mt-1 text-xs text-gray-400">Max 10 MB. AI will re-scan and update categorization.</p>
                            </div>
                            <button class="pm-btn-primary w-full py-2 text-sm" type="submit" form="receipt-attach-form" data-attach-submit>
                                <span data-attach-text>Attach & Scan</span>
                                <span class="hidden items-center gap-2" data-attach-loading>
                                    <span class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                    Scanning...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Lightbox preview (hidden) --}}
            <div class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70" id="receipt-lightbox" onclick="this.classList.add('hidden'); this.classList.remove('flex')">
                <img src="" alt="" id="lightbox-img" class="max-h-[90vh] max-w-[90vw] rounded-lg shadow-xl">
            </div>
        </section>

        <section class="pm-card p-4">
            <h2 class="font-bold text-gray-950">{{ $isRouteScreenshot ? 'Journey Details' : 'Receipt Details' }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @if ($isRouteScreenshot)
                    <input type="hidden" name="merchant_name" value="{{ $record->routeSourceName() }}">
                    <input type="hidden" name="merchant_address" value="">
                    <input type="hidden" name="receipt_number" value="">
                @else
                    <div class="sm:col-span-2">
                        <label class="pm-label" for="merchant_name">Merchant name</label>
                        <input class="pm-input" id="merchant_name" name="merchant_name" value="{{ old('merchant_name', $record->merchant_name) }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="pm-label" for="merchant_address">Merchant address</label>
                        <textarea class="pm-input min-h-20" id="merchant_address" name="merchant_address">{{ old('merchant_address', $record->merchant_address) }}</textarea>
                    </div>
                @endif
                <div>
                    <label class="pm-label" for="receipt_date">{{ $isRouteScreenshot ? 'Journey date' : 'Receipt date' }}</label>
                    <input class="pm-input" id="receipt_date" name="receipt_date" type="date" value="{{ old('receipt_date', $record->receipt_date?->format('Y-m-d')) }}">
                </div>
                <div>
                    <label class="pm-label" for="receipt_time">{{ $isRouteScreenshot ? 'Journey time' : 'Receipt time' }}</label>
                    <input class="pm-input" id="receipt_time" name="receipt_time" type="time" value="{{ old('receipt_time', $timeValue) }}">
                </div>
                @unless ($isRouteScreenshot)
                    <div>
                        <label class="pm-label" for="receipt_number">Receipt number</label>
                        <input class="pm-input" id="receipt_number" name="receipt_number" value="{{ old('receipt_number', $record->receipt_number) }}">
                    </div>
                @endunless
                <div>
                    <label class="pm-label" for="currency">Currency</label>
                    <input class="pm-input uppercase" id="currency" name="currency" maxlength="3" value="{{ old('currency', $record->currency ?: 'MYR') }}">
                </div>
                @if ($isRouteScreenshot)
                    <input id="total_amount" name="total_amount" type="hidden" value="{{ old('total_amount', $record->total_amount) }}">
                @else
                    <div>
                        <label class="pm-label" for="subtotal">Subtotal</label>
                        <input class="pm-input" id="subtotal" name="subtotal" type="number" step="0.01" value="{{ old('subtotal', $record->subtotal) }}">
                    </div>
                    <div>
                        <label class="pm-label" for="tax_amount">Tax amount</label>
                        <input class="pm-input" id="tax_amount" name="tax_amount" type="number" step="0.01" value="{{ old('tax_amount', $record->tax_amount) }}">
                    </div>
                    <div>
                        <label class="pm-label" for="service_charge">Service charge</label>
                        <input class="pm-input" id="service_charge" name="service_charge" type="number" step="0.01" value="{{ old('service_charge', $record->service_charge) }}">
                    </div>
                    <div>
                        <label class="pm-label" for="discount">Discount</label>
                        <input class="pm-input" id="discount" name="discount" type="number" step="0.01" value="{{ old('discount', $record->discount) }}">
                    </div>
                    <div>
                        <label class="pm-label" for="total_amount">Total amount</label>
                        <input class="pm-input" id="total_amount" name="total_amount" type="number" step="0.01" value="{{ old('total_amount', $record->total_amount) }}">
                    </div>
                    <div>
                        <label class="pm-label" for="payment_method">Payment method</label>
                        <input class="pm-input" id="payment_method" name="payment_method" value="{{ old('payment_method', $record->payment_method) }}">
                    </div>
                @endif
                <div>
                    <label class="pm-label" for="claim_expense_type">Claim type</label>
                    <select class="pm-input" id="claim_expense_type" name="claim_expense_type">
                        @foreach ([
                            'receipt' => 'Receipt',
                            'medical' => 'Medical Claim',
                            'hotel' => 'Hotel Receipt',
                            'mileage' => 'Mileage',
                            'toll' => 'Toll',
                            'parking' => 'Parking',
                            'travel' => 'Travel Claim',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected(old('claim_expense_type', $record->claim_expense_type ?: 'receipt') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="pm-label" for="expense_category_id">Expense category</label>
                    <select class="pm-input" id="expense_category_id" name="expense_category_id">
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('expense_category_id', $record->expense_category_id) === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="pm-label" for="department_id">Department</label>
                    <select class="pm-input" id="department_id" name="department_id">
                        <option value="">Select department</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) old('department_id', $record->department_id) === (string) $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="pm-label" for="project_cost_center">Project / cost center</label>
                    <input class="pm-input" id="project_cost_center" name="project_cost_center" value="{{ old('project_cost_center', $record->project_cost_center) }}">
                </div>
                <div class="sm:col-span-2 rounded-lg border border-gray-100 p-3">
                    <h3 class="font-semibold text-gray-950">Mileage, Toll & Parking</h3>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="pm-label" for="route_origin">From</label>
                            <input class="pm-input" id="route_origin" name="route_origin" value="{{ old('route_origin', $record->route_origin) }}">
                        </div>
                        <div>
                            <label class="pm-label" for="route_destination">To</label>
                            <input class="pm-input" id="route_destination" name="route_destination" value="{{ old('route_destination', $record->route_destination) }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="pm-label" for="route_summary">Route / via</label>
                            <input class="pm-input" id="route_summary" name="route_summary" value="{{ old('route_summary', $record->route_summary) }}">
                        </div>
                        <div>
                            <label class="pm-label" for="route_distance_km">Distance (km)</label>
                            <input class="pm-input" id="route_distance_km" name="route_distance_km" type="number" min="0" step="0.01" value="{{ old('route_distance_km', $record->route_distance_km) }}" data-mileage-distance>
                        </div>
                        <div>
                            <label class="pm-label" for="mileage_rate">Mileage rate / km</label>
                            <input class="pm-input" id="mileage_rate" name="mileage_rate" type="number" min="0" step="0.01" value="{{ old('mileage_rate', $record->mileage_rate ?: config('expenseflow.mileage.default_rate')) }}" data-mileage-rate>
                        </div>
                        <div>
                            <label class="pm-label" for="mileage_amount">Mileage amount</label>
                            <input class="pm-input bg-gray-50" id="mileage_amount" name="mileage_amount" type="number" min="0" step="0.01" value="{{ old('mileage_amount', $record->mileage_amount) }}" data-mileage-amount readonly>
                        </div>
                        <div>
                            <label class="pm-label" for="route_duration_minutes">Duration (minutes)</label>
                            <input class="pm-input" id="route_duration_minutes" name="route_duration_minutes" type="number" min="0" step="1" value="{{ old('route_duration_minutes', $record->route_duration_minutes) }}">
                        </div>
                        <div>
                            <label class="pm-label" for="route_arrival_time">ETA</label>
                            <input class="pm-input" id="route_arrival_time" name="route_arrival_time" value="{{ old('route_arrival_time', $record->route_arrival_time) }}">
                        </div>
                        <div class="sm:col-span-2">
                            <div class="flex items-center justify-between gap-3 max-sm:flex-col max-sm:items-stretch">
                                <label class="pm-label mb-0" for="toll_amount">Tolls</label>
                                <button class="pm-btn-secondary px-3 py-2 text-sm" type="button" data-add-toll>Add Toll</button>
                            </div>
                            <input id="toll_amount" name="toll_amount" type="hidden" value="{{ old('toll_amount', $record->toll_amount) }}" data-travel-component data-toll-total>
                            <div class="mt-2 space-y-2" data-toll-list>
                                @for ($i = 0; $i < $tollEntryCount; $i++)
                                    @php $tollEntry = $tollEntries[$i] ?? []; @endphp
                                    <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_10rem_auto]" data-toll-row>
                                        <input class="pm-input" name="toll_entries[{{ $i }}][label]" placeholder="Toll name / plaza" value="{{ $tollEntry['label'] ?? '' }}" data-toll-label>
                                        <input class="pm-input" name="toll_entries[{{ $i }}][amount]" type="number" min="0" step="0.01" placeholder="Amount" value="{{ $tollEntry['amount'] ?? '' }}" data-toll-entry-amount>
                                        <button class="pm-btn-secondary px-3 py-2" type="button" data-remove-toll>Remove</button>
                                    </div>
                                @endfor
                            </div>
                        </div>
                        <div>
                            <label class="pm-label" for="parking_amount">Parking amount</label>
                            <input class="pm-input" id="parking_amount" name="parking_amount" type="number" min="0" step="0.01" value="{{ old('parking_amount', $record->parking_amount) }}" data-travel-component>
                        </div>
                        @if ($isRouteScreenshot)
                            <div class="sm:col-span-2">
                                <label class="pm-label" for="subtotal">Subtotal (mileage + toll + parking)</label>
                                <input class="pm-input bg-gray-50" id="subtotal" name="subtotal" type="number" min="0" step="0.01" value="{{ old('subtotal', $record->subtotal) }}" readonly>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="sm:col-span-2 rounded-lg border border-gray-100 p-3" id="medical-details-section">
                    <h3 class="font-semibold text-gray-950">Medical Claim Details</h3>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="pm-label" for="medical_patient_name">Patient Name</label>
                            <input class="pm-input" id="medical_patient_name" name="medical_patient_name" value="{{ old('medical_patient_name', $record->medical_patient_name) }}" placeholder="Full name of patient">
                        </div>
                        <div>
                            <label class="pm-label" for="medical_relationship">Relationship to Claimant</label>
                            <select class="pm-input" id="medical_relationship" name="medical_relationship">
                                <option value="">Select relationship</option>
                                @foreach (['self' => 'Self', 'spouse' => 'Spouse', 'child' => 'Child', 'parent' => 'Parent', 'sibling' => 'Sibling', 'other' => 'Other'] as $val => $lbl)
                                    <option value="{{ $val }}" @selected(old('medical_relationship', $record->medical_relationship) === $val)>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="pm-label" for="medical_doctor_name">Doctor Name</label>
                            <input class="pm-input" id="medical_doctor_name" name="medical_doctor_name" value="{{ old('medical_doctor_name', $record->medical_doctor_name) }}" placeholder="Attending doctor">
                        </div>
                        <div>
                            <label class="pm-label" for="medical_diagnosis">Diagnosis / Reason for Visit</label>
                            <input class="pm-input" id="medical_diagnosis" name="medical_diagnosis" value="{{ old('medical_diagnosis', $record->medical_diagnosis) }}" placeholder="e.g. Fever, Flu, Checkup">
                        </div>
                        <div>
                            <label class="pm-label" for="medical_consultation_fee">Consultation Fee (MYR)</label>
                            <input class="pm-input" id="medical_consultation_fee" name="medical_consultation_fee" type="number" step="0.01" min="0" value="{{ old('medical_consultation_fee', $record->medical_consultation_fee) }}" data-med-consultation>
                        </div>
                        <div>
                            <label class="pm-label" for="medical_medication_fee">Medication Fee (MYR)</label>
                            <input class="pm-input" id="medical_medication_fee" name="medical_medication_fee" type="number" step="0.01" min="0" value="{{ old('medical_medication_fee', $record->medical_medication_fee) }}" data-med-medication>
                        </div>
                        <div class="sm:col-span-2 flex items-center gap-3">
                            <input class="h-4 w-4 rounded border-gray-300 text-[#D71920]" id="medical_panel_clinic" name="medical_panel_clinic" type="checkbox" value="1" @checked(old('medical_panel_clinic', $record->medical_panel_clinic))>
                            <label class="pm-label mb-0 cursor-pointer" for="medical_panel_clinic">Panel clinic (company-approved)</label>
                        </div>
                    </div>
                </div>

                <div class="sm:col-span-2 rounded-lg border border-gray-100 p-3" id="hotel-details-section">
                    <h3 class="font-semibold text-gray-950">Hotel Details</h3>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="pm-label" for="hotel_check_in_date">Check-in Date</label>
                            <input class="pm-input" id="hotel_check_in_date" name="hotel_check_in_date" type="date" value="{{ old('hotel_check_in_date', $record->hotel_check_in_date?->format('Y-m-d')) }}" data-hotel-checkin>
                        </div>
                        <div>
                            <label class="pm-label" for="hotel_check_out_date">Check-out Date</label>
                            <input class="pm-input" id="hotel_check_out_date" name="hotel_check_out_date" type="date" value="{{ old('hotel_check_out_date', $record->hotel_check_out_date?->format('Y-m-d')) }}" data-hotel-checkout>
                        </div>
                        <div>
                            <label class="pm-label" for="hotel_check_in_time">Check-in Time</label>
                            <input class="pm-input" id="hotel_check_in_time" name="hotel_check_in_time" type="time" value="{{ old('hotel_check_in_time', $record->hotel_check_in_time) }}">
                        </div>
                        <div>
                            <label class="pm-label" for="hotel_check_out_time">Check-out Time</label>
                            <input class="pm-input" id="hotel_check_out_time" name="hotel_check_out_time" type="time" value="{{ old('hotel_check_out_time', $record->hotel_check_out_time) }}">
                        </div>
                        <div>
                            <label class="pm-label" for="hotel_room_number">Room Number</label>
                            <input class="pm-input" id="hotel_room_number" name="hotel_room_number" value="{{ old('hotel_room_number', $record->hotel_room_number) }}">
                        </div>
                        <div>
                            <label class="pm-label" for="hotel_room_type">Room Type</label>
                            <input class="pm-input" id="hotel_room_type" name="hotel_room_type" value="{{ old('hotel_room_type', $record->hotel_room_type) }}">
                        </div>
                        <div>
                            <label class="pm-label" for="hotel_num_nights">Number of Nights</label>
                            <input class="pm-input bg-gray-50" id="hotel_num_nights" name="hotel_num_nights" type="number" min="1" value="{{ old('hotel_num_nights', $record->hotel_num_nights) }}" data-hotel-nights readonly>
                        </div>
                        <div>
                            <label class="pm-label" for="hotel_num_adults">Adults</label>
                            <input class="pm-input" id="hotel_num_adults" name="hotel_num_adults" type="number" min="0" value="{{ old('hotel_num_adults', $record->hotel_num_adults) }}">
                        </div>
                        <div>
                            <label class="pm-label" for="hotel_num_children">Children</label>
                            <input class="pm-input" id="hotel_num_children" name="hotel_num_children" type="number" min="0" value="{{ old('hotel_num_children', $record->hotel_num_children) }}">
                        </div>
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label class="pm-label" for="description">Purpose / description</label>
                    <textarea class="pm-input min-h-24" id="description" name="description">{{ old('description', $record->description) }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="pm-label" for="remarks">Staff remarks</label>
                    <textarea class="pm-input min-h-20" id="remarks" name="remarks">{{ old('remarks', $record->remarks) }}</textarea>
                </div>
            </div>
        </section>
    </div>

    @unless ($isRouteScreenshot)
        <section class="pm-card p-4">
            <h2 class="font-bold text-gray-950">Receipt Items</h2>
            <div class="mt-4 space-y-3">
                @for ($i = 0; $i < $itemCount; $i++)
                    @php $item = $items[$i] ?? []; @endphp
                    <div class="grid gap-3 rounded-lg border border-gray-100 p-3 sm:grid-cols-[minmax(0,1.4fr)_0.6fr_0.8fr_0.8fr]">
                        <div>
                            <label class="pm-label" for="items_{{ $i }}_description">Description</label>
                            <input class="pm-input" id="items_{{ $i }}_description" name="items[{{ $i }}][description]" value="{{ $item['description'] ?? '' }}">
                        </div>
                        <div>
                            <label class="pm-label" for="items_{{ $i }}_quantity">Qty</label>
                            <input class="pm-input" id="items_{{ $i }}_quantity" name="items[{{ $i }}][quantity]" type="number" step="0.01" value="{{ $item['quantity'] ?? '' }}">
                        </div>
                        <div>
                            <label class="pm-label" for="items_{{ $i }}_unit_price">Unit price</label>
                            <input class="pm-input" id="items_{{ $i }}_unit_price" name="items[{{ $i }}][unit_price]" type="number" step="0.01" value="{{ $item['unit_price'] ?? '' }}">
                        </div>
                        <div>
                            <label class="pm-label" for="items_{{ $i }}_amount">Amount</label>
                            <input class="pm-input" id="items_{{ $i }}_amount" name="items[{{ $i }}][amount]" type="number" step="0.01" value="{{ $item['amount'] ?? '' }}">
                        </div>
                    </div>
                @endfor
            </div>
        </section>
    @endunless

    <div class="pm-mobile-action-bar">
        <div class="grid gap-2 sm:grid-cols-3">
            <button class="pm-btn-secondary" type="submit" name="intent_override" value="save">Save Draft</button>
            <button class="pm-btn-primary" type="submit">Submit for Approval</button>
            <button class="pm-btn-secondary" type="submit" name="intent_override" value="non_claimable">Save as Record</button>
        </div>
    </div>
</form>

@if ($canEdit)
    @foreach ($record->receipts as $receipt)
        <form id="receipt-update-form-{{ $receipt->id }}" method="POST" action="{{ route('records.receipts.update', [$record, $receipt]) }}" class="hidden">
            @csrf
            @method('PATCH')
        </form>

        @if ($record->receipts->count() > 1)
            <form id="receipt-delete-form-{{ $receipt->id }}" method="POST" action="{{ route('records.receipts.destroy', [$record, $receipt]) }}" class="hidden" onsubmit="return confirm('Remove this receipt?')">
                @csrf
                @method('DELETE')
            </form>
        @endif
    @endforeach

    <form id="receipt-attach-form" method="POST" action="{{ route('records.receipts.store', $record) }}" enctype="multipart/form-data" class="hidden">
        @csrf
    </form>
@endif

<script>
(function () {
    const typeSelect = document.getElementById('claim_expense_type');

    // Medical section show/hide + auto-sum consultation + medication → total
    const medicalSection = document.getElementById('medical-details-section');
    const medConsult = document.querySelector('[data-med-consultation]');
    const medMedication = document.querySelector('[data-med-medication]');
    const totalInput = document.getElementById('total_amount');

    function toggleMedicalSection() {
        medicalSection.style.display = typeSelect.value === 'medical' ? '' : 'none';
    }

    function sumMedicalFees() {
        const c = parseFloat(medConsult.value) || 0;
        const m = parseFloat(medMedication.value) || 0;
        if ((c + m) > 0 && totalInput) totalInput.value = (c + m).toFixed(2);
    }

    medConsult && medConsult.addEventListener('input', sumMedicalFees);
    medMedication && medMedication.addEventListener('input', sumMedicalFees);
    typeSelect.addEventListener('change', toggleMedicalSection);
    toggleMedicalSection();

    // Hotel section show/hide + nights auto-calc
    const hotelSection = document.getElementById('hotel-details-section');
    const checkinInput = document.querySelector('[data-hotel-checkin]');
    const checkoutInput = document.querySelector('[data-hotel-checkout]');
    const nightsInput = document.querySelector('[data-hotel-nights]');

    function toggleHotelSection() {
        hotelSection.style.display = typeSelect.value === 'hotel' ? '' : 'none';
    }

    function calcNights() {
        if (!checkinInput.value || !checkoutInput.value) return;
        const diff = (new Date(checkoutInput.value) - new Date(checkinInput.value)) / 86400000;
        nightsInput.value = diff > 0 ? diff : '';
    }

    typeSelect.addEventListener('change', toggleHotelSection);
    checkinInput.addEventListener('change', calcNights);
    checkoutInput.addEventListener('change', calcNights);
    toggleHotelSection();

    // Attach another receipt toggle
    const toggleBtn = document.querySelector('[data-toggle-attach]');
    const attachForm = document.getElementById('attach-form');
    if (toggleBtn && attachForm) {
        toggleBtn.addEventListener('click', () => {
            attachForm.classList.toggle('hidden');
        });
        const attachFormEl = document.getElementById('receipt-attach-form');
        if (attachFormEl) {
            attachFormEl.addEventListener('submit', function () {
                const btn = document.querySelector('[data-attach-submit]');
                const text = document.querySelector('[data-attach-text]');
                const loading = document.querySelector('[data-attach-loading]');
                if (btn) btn.disabled = true;
                if (text) text.classList.add('hidden');
                if (loading) { loading.classList.remove('hidden'); loading.classList.add('flex'); }
            });
        }
    }

    // Lightbox for receipt thumbnails
    const lightbox = document.getElementById('receipt-lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    document.querySelectorAll('[data-preview-src]').forEach(img => {
        img.addEventListener('click', function () {
            lightboxImg.src = this.dataset.previewSrc;
            lightboxImg.alt = this.dataset.previewName || '';
            lightbox.classList.remove('hidden');
            lightbox.classList.add('flex');
        });
    });
})();
</script>
