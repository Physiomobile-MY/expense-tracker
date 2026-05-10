<?php

namespace App\Jobs;

class ProcessReceiptExtractionJob
{
    public function __construct(public int $expenseRecordId)
    {
    }

    public function handle(): void
    {
        // Hook into OpenAIReceiptExtractionService and persist AI extraction logs.
    }
}
