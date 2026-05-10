@if ($record->canBeVoidedBy(auth()->user()))
    <section class="pm-card mt-4 border border-red-100 p-4">
        <h2 class="font-bold text-gray-950">Void Claim</h2>
        <p class="mt-1 text-sm text-gray-500">Use this for wrong uploads or duplicate receipts. A reason is required and the record stays in the audit trail.</p>
        <form method="POST" action="{{ route('records.void', $record) }}" class="mt-4 space-y-3">
            @csrf
            <label class="pm-label" for="void_reason_{{ $record->id }}">Reason</label>
            <textarea class="pm-input min-h-20" id="void_reason_{{ $record->id }}" name="reason" placeholder="Example: Uploaded the wrong receipt / duplicate upload" required></textarea>
            <button class="w-full rounded-lg bg-gray-900 px-4 py-3 text-sm font-semibold text-white shadow-sm" type="submit">Void Claim</button>
        </form>
    </section>
@endif
