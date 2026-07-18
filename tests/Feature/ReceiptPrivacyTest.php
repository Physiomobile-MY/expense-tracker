<?php

namespace Tests\Feature;

use App\Jobs\ProcessReceiptExtractionJob;
use App\Models\AIExtractionLog;
use App\Models\ExpenseReceipt;
use App\Models\ExpenseRecord;
use App\Models\User;
use App\Services\ExpenseRecordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_receipts_use_private_receipts_disk_and_authorized_route_url(): void
    {
        $this->seed();
        Storage::fake('receipts');

        $user = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $record = app(ExpenseRecordService::class)->createDraftFromUpload(
            $user,
            UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
            ExpenseReceipt::DOCUMENT_TYPE_RECEIPT,
        );

        $receipt = $record->receipts()->firstOrFail();
        Storage::disk('receipts')->assertExists($receipt->file_path);

        $this->assertStringNotContainsString('/storage/', $receipt->url());
        $this->assertStringContainsString(route('receipts.file', $receipt, false), $receipt->url());
    }

    public function test_missing_openai_key_skips_extraction_without_raw_prompt_or_response_logs(): void
    {
        $this->seed();
        config()->set('services.openai.key', null);
        config()->set('services.openai.receipt_extraction_enabled', true);
        Storage::fake('receipts');

        $user = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $record = ExpenseRecord::create([
            'user_id' => $user->id,
            'record_type' => ExpenseRecord::TYPE_CLAIMABLE,
            'status' => 'draft',
            'merchant_name' => 'Test Merchant',
            'receipt_date' => now()->toDateString(),
            'currency' => 'MYR',
            'total_amount' => 10,
        ]);
        $path = 'receipts/test-missing-key.pdf';
        Storage::disk('receipts')->put($path, 'redacted-test-receipt');
        $receipt = ExpenseReceipt::create([
            'expense_record_id' => $record->id,
            'original_filename' => 'test-missing-key.pdf',
            'file_path' => $path,
            'file_type' => 'application/pdf',
            'file_size' => 123,
            'uploaded_by' => $user->id,
            'document_type' => ExpenseReceipt::DOCUMENT_TYPE_RECEIPT,
        ]);

        ProcessReceiptExtractionJob::dispatchSync($record->id, $receipt->id);

        $log = AIExtractionLog::firstOrFail();
        $this->assertSame('skipped', $log->status);
        $this->assertNull($log->prompt);
        $this->assertNull($log->raw_response);
        $this->assertNull($log->extracted_json);
        $this->assertSame('AI receipt extraction unavailable; manual entry required.', $log->error_message);
    }
}
