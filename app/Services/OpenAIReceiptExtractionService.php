<?php

namespace App\Services;

class OpenAIReceiptExtractionService
{
    public const PROMPT = <<<'PROMPT'
You are a receipt extraction assistant for Physiomobile's internal expense management system.

Analyze the uploaded receipt image or PDF and extract the information into valid JSON only.
PROMPT;

    public function extractFromFile(string $filePath): array
    {
        return [
            'status' => 'queued_or_stubbed',
            'file_path' => $filePath,
            'message' => 'Implement OpenAI Responses API call in Laravel runtime.',
        ];
    }
}
