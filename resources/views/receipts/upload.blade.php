@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="mb-5">
        <p class="text-sm font-semibold text-[#D71920]">Claim Capture</p>
        <h1 class="text-2xl font-bold text-gray-950">Upload Claim Evidence</h1>
    </div>

    <section class="pm-card p-5">
        <form method="POST" action="{{ route('receipts.store') }}" enctype="multipart/form-data" class="space-y-5" data-upload-form>
            @csrf
            <div>
                <label class="pm-label" for="document_type">Upload type</label>
                <select class="pm-input" id="document_type" name="document_type" required>
                    <option value="receipt" @selected(old('document_type') === 'receipt')>Receipt</option>
                    <option value="waze_screenshot" @selected(old('document_type') === 'waze_screenshot')>Waze screenshot</option>
                </select>
            </div>
            <div>
                <label class="pm-label" for="receipt">File</label>
                <input class="pm-input file:mr-3 file:rounded-md file:border-0 file:bg-[#FDECEC] file:px-3 file:py-2 file:text-sm file:font-semibold file:text-[#A80F16]" id="receipt" name="receipt" type="file" accept=".jpg,.jpeg,.png,.heic,.heif,.pdf,image/jpeg,image/png,image/heic,image/heif,application/pdf" required>
                <p class="mt-2 text-xs text-gray-500">JPG, PNG, HEIC, HEIF, or PDF. Maximum 10MB.</p>
            </div>

            <div class="rounded-lg bg-[#FDECEC] p-4 text-sm text-[#A80F16]">
                <p class="font-semibold">Reading your upload...</p>
                <p class="mt-1">Extracting receipt details or Waze distance, toll, and route data. Please review the result before submitting.</p>
            </div>

            <div class="hidden rounded-lg border border-red-100 bg-white p-4 text-sm text-gray-700" data-upload-loading>
                <div class="flex items-center gap-3">
                    <span class="h-5 w-5 shrink-0 animate-spin rounded-full border-2 border-[#F3BEC7] border-t-[#D71920]"></span>
                    <div>
                        <p class="font-semibold text-gray-950">Scanning upload...</p>
                        <p class="mt-1">Uploading file and reading amount, distance, toll, and route details.</p>
                    </div>
                </div>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-[#FDECEC]">
                    <div class="h-full w-1/2 animate-pulse rounded-full bg-[#D71920]"></div>
                </div>
            </div>

            <button class="pm-btn-primary w-full" type="submit" data-upload-button>
                <span data-upload-button-text>Upload</span>
                <span class="hidden items-center gap-2" data-upload-button-loading>
                    <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    Scanning...
                </span>
            </button>
        </form>
    </section>
</div>
@endsection
