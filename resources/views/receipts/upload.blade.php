@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="mb-5">
        <p class="text-sm font-semibold text-[#D71920]">Receipt Capture</p>
        <h1 class="text-2xl font-bold text-gray-950">Upload Receipt</h1>
    </div>

    <section class="pm-card p-5">
        <form method="POST" action="{{ route('receipts.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <div>
                <label class="pm-label" for="receipt">Receipt file</label>
                <input class="pm-input file:mr-3 file:rounded-md file:border-0 file:bg-[#FDECEC] file:px-3 file:py-2 file:text-sm file:font-semibold file:text-[#A80F16]" id="receipt" name="receipt" type="file" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" required>
                <p class="mt-2 text-xs text-gray-500">JPG, PNG, or PDF. Maximum 10MB.</p>
            </div>

            <div class="rounded-lg bg-[#FDECEC] p-4 text-sm text-[#A80F16]">
                <p class="font-semibold">Reading your receipt...</p>
                <p class="mt-1">Extracting merchant, date, amount, and payment details. Please review the result before submitting.</p>
            </div>

            <button class="pm-btn-primary w-full" type="submit">Upload Receipt</button>
        </form>
    </section>
</div>
@endsection
