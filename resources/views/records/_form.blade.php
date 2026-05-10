@php
    $primaryReceipt = $record->receipts->first();
    $timeValue = $record->receipt_time ? (is_string($record->receipt_time) ? substr($record->receipt_time, 0, 5) : $record->receipt_time->format('H:i')) : '';
    $items = old('items', $record->items->map(fn ($item) => [
        'description' => $item->description,
        'quantity' => $item->quantity,
        'unit_price' => $item->unit_price,
        'amount' => $item->amount,
    ])->toArray());
    $itemCount = max(count($items), 5);
@endphp

<form method="POST" action="{{ route('records.update', $record) }}" class="space-y-5">
    @csrf
    @method('PUT')

    <div class="grid gap-4 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
        <section class="pm-card overflow-hidden">
            <div class="border-b border-gray-100 px-4 py-3">
                <h2 class="font-bold text-gray-950">Receipt Preview</h2>
            </div>
            <div class="p-4">
                @if ($primaryReceipt?->isImage())
                    <img src="{{ route('receipts.file', $primaryReceipt) }}" alt="Receipt preview" class="max-h-[34rem] w-full rounded-lg border border-gray-200 object-contain">
                @elseif ($primaryReceipt)
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-5 text-center">
                        <p class="font-semibold text-gray-900">{{ $primaryReceipt->original_filename }}</p>
                        <a href="{{ route('receipts.file', $primaryReceipt) }}" class="mt-3 inline-flex text-sm font-semibold text-[#D71920]" target="_blank">Open PDF</a>
                    </div>
                @else
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-5 text-center text-sm text-gray-500">No receipt file.</div>
                @endif

                @if ($record->ai_confidence_score !== null && $record->ai_confidence_score < 0.75)
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                        Some receipt details may be inaccurate. Please double-check before submitting.
                    </div>
                @endif

                @if ($record->aiLogs->last()?->status === 'failed')
                    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                        We could not read this receipt clearly. You can still enter the details manually.
                    </div>
                @endif
            </div>
        </section>

        <section class="pm-card p-4">
            <h2 class="font-bold text-gray-950">Receipt Details</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="pm-label" for="merchant_name">Merchant name</label>
                    <input class="pm-input" id="merchant_name" name="merchant_name" value="{{ old('merchant_name', $record->merchant_name) }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="pm-label" for="merchant_address">Merchant address</label>
                    <textarea class="pm-input min-h-20" id="merchant_address" name="merchant_address">{{ old('merchant_address', $record->merchant_address) }}</textarea>
                </div>
                <div>
                    <label class="pm-label" for="receipt_date">Receipt date</label>
                    <input class="pm-input" id="receipt_date" name="receipt_date" type="date" value="{{ old('receipt_date', $record->receipt_date?->format('Y-m-d')) }}">
                </div>
                <div>
                    <label class="pm-label" for="receipt_time">Receipt time</label>
                    <input class="pm-input" id="receipt_time" name="receipt_time" type="time" value="{{ old('receipt_time', $timeValue) }}">
                </div>
                <div>
                    <label class="pm-label" for="receipt_number">Receipt number</label>
                    <input class="pm-input" id="receipt_number" name="receipt_number" value="{{ old('receipt_number', $record->receipt_number) }}">
                </div>
                <div>
                    <label class="pm-label" for="currency">Currency</label>
                    <input class="pm-input uppercase" id="currency" name="currency" maxlength="3" value="{{ old('currency', $record->currency ?: 'MYR') }}">
                </div>
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

    <div class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm lg:sticky lg:bottom-4 lg:z-20 lg:shadow-lg">
        <div class="grid gap-2 sm:grid-cols-3">
            <button class="pm-btn-secondary" type="submit" name="intent" value="save">Save Draft</button>
            <button class="pm-btn-primary" type="submit" name="intent" value="claimable">Submit for Approval</button>
            <button class="pm-btn-secondary" type="submit" name="intent" value="non_claimable">Save as Record</button>
        </div>
    </div>
</form>
