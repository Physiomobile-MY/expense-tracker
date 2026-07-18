<?php

namespace App\Jobs;

use App\Models\AIExtractionLog;
use App\Models\ExpenseRecord;
use App\Services\ExpenseRecordService;
use App\Services\OpenAIReceiptExtractionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessReceiptExtractionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(
        public readonly int $expenseRecordId,
        public readonly ?int $expenseReceiptId = null,
    ) {}

    public function handle(OpenAIReceiptExtractionService $extractor, ExpenseRecordService $records): void
    {
        $record = ExpenseRecord::with('receipts')->findOrFail($this->expenseRecordId);
        $receipts = $this->expenseReceiptId
            ? $record->receipts()->whereKey($this->expenseReceiptId)->get()
            : $record->receipts;

        foreach ($receipts as $receipt) {
            try {
                $result = $extractor->extract($receipt);

                if ($result['skipped'] ?? false) {
                    AIExtractionLog::create([
                        'expense_record_id' => $record->id,
                        'provider' => 'openai',
                        'model' => config('services.openai.receipt_model'),
                        'status' => 'skipped',
                        'error_message' => 'AI receipt extraction unavailable; manual entry required.',
                    ]);

                    continue;
                }

                AIExtractionLog::create([
                    'expense_record_id' => $record->id,
                    'provider' => 'openai',
                    'model' => $result['model'] ?? config('services.openai.receipt_model'),
                    'confidence_score' => $result['confidence_score'],
                    'status' => 'completed',
                    'token_usage_input' => $result['token_usage_input'],
                    'token_usage_output' => $result['token_usage_output'],
                ]);

                $records->applyExtraction($record, $result['extracted_json']);
            } catch (Throwable $exception) {
                AIExtractionLog::create([
                    'expense_record_id' => $record->id,
                    'provider' => 'openai',
                    'model' => config('services.openai.receipt_model'),
                    'status' => 'failed',
                    'error_message' => str($exception->getMessage())->limit(255)->toString(),
                ]);
            }
        }
    }
}
