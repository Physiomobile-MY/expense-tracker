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
                    <option value="google_maps_screenshot" @selected(old('document_type') === 'google_maps_screenshot')>Google Maps screenshot</option>
                </select>
            </div>

            <div>
                <label class="pm-label" for="receipts">Files</label>
                <input
                    class="pm-input file:mr-3 file:rounded-md file:border-0 file:bg-[#FDECEC] file:px-3 file:py-2 file:text-sm file:font-semibold file:text-[#A80F16]"
                    id="receipts"
                    name="receipts[]"
                    type="file"
                    accept=".jpg,.jpeg,.png,.heic,.heif,.pdf,image/jpeg,image/png,image/heic,image/heif,application/pdf"
                    multiple
                    required
                    data-file-input
                >
                <p class="mt-2 text-xs text-gray-500">JPG, PNG, HEIC, HEIF, or PDF. Max 10 MB each. Select multiple files to upload in one go.</p>
                <div class="mt-2 hidden space-y-1 text-sm text-gray-700" id="file-list"></div>
            </div>

            <div class="rounded-lg bg-[#FDECEC] p-4 text-sm text-[#A80F16]" data-upload-hint>
                <p class="font-semibold">Reading your upload...</p>
                <p class="mt-1">Extracting receipt details or route distance, toll, and map data. Please review the result before submitting.</p>
            </div>

            <div class="hidden rounded-lg border border-red-100 bg-white p-4 text-sm text-gray-700" data-upload-loading>
                <div class="flex items-center gap-3">
                    <span class="h-5 w-5 shrink-0 animate-spin rounded-full border-2 border-[#F3BEC7] border-t-[#D71920]"></span>
                    <div>
                        <p class="font-semibold text-gray-950">Scanning uploads...</p>
                        <p class="mt-1">Uploading files and reading amount, distance, toll, and route details.</p>
                    </div>
                </div>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-[#FDECEC]">
                    <div class="h-full animate-pulse rounded-full bg-[#D71920]" data-progress-bar style="width: 30%"></div>
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

<script>
(function () {
    const input = document.querySelector('[data-file-input]');
    const fileList = document.getElementById('file-list');
    const hint = document.querySelector('[data-upload-hint]');
    const loading = document.querySelector('[data-upload-loading]');
    const btn = document.querySelector('[data-upload-button]');
    const btnText = document.querySelector('[data-upload-button-text]');
    const btnLoading = document.querySelector('[data-upload-button-loading]');
    const form = document.querySelector('[data-upload-form]');

    input.addEventListener('change', function () {
        const files = Array.from(this.files);
        fileList.innerHTML = '';
        if (!files.length) { fileList.classList.add('hidden'); return; }

        fileList.classList.remove('hidden');
        files.forEach(f => {
            const div = document.createElement('div');
            div.className = 'flex items-center gap-2 rounded-md bg-gray-50 px-3 py-2';
            div.innerHTML = `<span class="text-gray-400">📄</span><span class="flex-1 truncate">${f.name}</span><span class="shrink-0 text-gray-400">${(f.size / 1024 / 1024).toFixed(1)} MB</span>`;
            fileList.appendChild(div);
        });

        const count = files.length;
        btnText.textContent = count > 1 ? `Upload ${count} files` : 'Upload';
    });

    form.addEventListener('submit', function () {
        hint.classList.add('hidden');
        loading.classList.remove('hidden');
        btn.disabled = true;
        btnText.classList.add('hidden');
        btnLoading.classList.remove('hidden');
        btnLoading.classList.add('flex');
    });
})();
</script>
@endsection
