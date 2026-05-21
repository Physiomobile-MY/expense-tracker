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
    ) {}

    public function handle(OpenAIReceiptExtractionService $extractor, ExpenseRecordService $records): void
    {
        $record = ExpenseRecord::with('receipts')->findOrFail($this->expenseRecordId);
        $receipt = $record->receipts()->latest()->first();

        if (! $receipt) {
            return;
        }

        try {
            $result = $extractor->extract($receipt);

            AIExtractionLog::create([
                'expense_record_id' => $record->id,
                'provider' => 'openai',
                'model' => $result['model'] ?? config('services.openai.receipt_model'),
                'prompt' => $result['prompt'],
                'raw_response' => json_encode($result['raw_response']),
                'extracted_json' => $result['extracted_json'],
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
                'prompt' => config('expenseflow.receipt_prompt'),
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
