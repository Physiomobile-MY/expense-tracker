<?php

namespace App\Services;

use App\Models\AIExtractionLog;
use App\Models\ExpenseReceipt;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class OpenAIReceiptExtractionService
{
    public function extract(ExpenseReceipt $receipt): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('AI receipt extraction is disabled.');
        }

        $apiKey = config('services.openai.key');

        if (! $apiKey) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        if ($this->dailyLimitReached()) {
            throw new RuntimeException('Daily AI receipt scan limit reached.');
        }

        $path = Storage::path($receipt->file_path);

        if (! is_file($path)) {
            throw new RuntimeException('Receipt file is missing from storage.');
        }

        $prompt = config('expenseflow.receipt_prompt');
        $payload = [
            'model' => $this->model(),
            'instructions' => $prompt,
            'input' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => 'Extract this Physiomobile expense receipt into the requested JSON schema.',
                        ],
                        $this->fileInput($receipt, $path),
                    ],
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'receipt_extraction',
                    'description' => 'Structured receipt details for Physiomobile ExpenseFlow.',
                    'strict' => false,
                    'schema' => $this->schema(),
                ],
            ],
        ];

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(90)
            ->post('https://api.openai.com/v1/responses', $payload);

        $raw = $response->json();

        if (! $response->successful()) {
            throw new RuntimeException(data_get($raw, 'error.message', 'OpenAI receipt extraction failed.'));
        }

        $json = $this->decodeOutput($raw);

        return [
            'prompt' => $prompt,
            'raw_response' => $raw,
            'extracted_json' => $json,
            'confidence_score' => $json['confidence_score'] ?? null,
            'token_usage_input' => data_get($raw, 'usage.input_tokens'),
            'token_usage_output' => data_get($raw, 'usage.output_tokens'),
        ];
    }

    private function dailyLimitReached(): bool
    {
        $limit = $this->dailyLimit();

        if ($limit < 1) {
            return false;
        }

        return AIExtractionLog::query()
            ->where('provider', 'openai')
            ->whereDate('created_at', now()->toDateString())
            ->count() >= $limit;
    }

    private function fileInput(ExpenseReceipt $receipt, string $path): array
    {
        $base64 = base64_encode(file_get_contents($path));

        if ($receipt->isPdf()) {
            return [
                'type' => 'input_file',
                'filename' => $receipt->original_filename,
                'file_data' => $base64,
            ];
        }

        return [
            'type' => 'input_image',
            'image_url' => 'data:'.$receipt->file_type.';base64,'.$base64,
            'detail' => 'high',
        ];
    }

    private function enabled(): bool
    {
        $setting = $this->settings();

        return (bool) (($setting['enabled'] ?? null) ?? config('services.openai.receipt_extraction_enabled'));
    }

    private function model(): string
    {
        $setting = $this->settings();

        return (string) (($setting['model'] ?? null) ?: config('services.openai.receipt_model', 'gpt-4.1-mini'));
    }

    private function dailyLimit(): int
    {
        $setting = $this->settings();

        return (int) (($setting['daily_scan_limit'] ?? null) ?? config('services.openai.daily_scan_limit', 50));
    }

    private function settings(): array
    {
        return SystemSetting::where('key', 'openai')->first()?->value ?? [];
    }

    private function decodeOutput(array $payload): array
    {
        $text = data_get($payload, 'output_text');

        if (! $text) {
            foreach ((array) data_get($payload, 'output', []) as $item) {
                foreach ((array) data_get($item, 'content', []) as $content) {
                    if (($content['type'] ?? null) === 'output_text') {
                        $text = $content['text'] ?? null;
                        break 2;
                    }
                }
            }
        }

        $decoded = json_decode((string) $text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('OpenAI returned an unreadable receipt JSON response.');
        }

        return $decoded;
    }

    private function schema(): array
    {
        $nullableString = ['type' => ['string', 'null']];
        $nullableNumber = ['type' => ['number', 'null']];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [
                'merchant_name',
                'merchant_address',
                'receipt_date',
                'receipt_time',
                'currency',
                'subtotal',
                'tax_amount',
                'service_charge',
                'discount',
                'total_amount',
                'payment_method',
                'receipt_number',
                'items',
                'confidence_score',
                'notes',
            ],
            'properties' => [
                'merchant_name' => $nullableString,
                'merchant_address' => $nullableString,
                'receipt_date' => $nullableString,
                'receipt_time' => $nullableString,
                'currency' => ['type' => ['string', 'null'], 'default' => 'MYR'],
                'subtotal' => $nullableNumber,
                'tax_amount' => $nullableNumber,
                'service_charge' => $nullableNumber,
                'discount' => $nullableNumber,
                'total_amount' => $nullableNumber,
                'payment_method' => $nullableString,
                'receipt_number' => $nullableString,
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['description', 'quantity', 'unit_price', 'amount'],
                        'properties' => [
                            'description' => $nullableString,
                            'quantity' => $nullableNumber,
                            'unit_price' => $nullableNumber,
                            'amount' => $nullableNumber,
                        ],
                    ],
                ],
                'confidence_score' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                'notes' => $nullableString,
            ],
        ];
    }
}
