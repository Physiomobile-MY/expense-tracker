<?php

return [
    'ai_receipt_extraction_enabled' => env('AI_RECEIPT_EXTRACTION_ENABLED', true),
    'ai_daily_scan_limit' => (int) env('AI_DAILY_SCAN_LIMIT', 50),
    'openai_model' => env('OPENAI_RECEIPT_MODEL', 'gpt-4.1-mini'),
    'theme' => [
        'primary' => env('APP_THEME_PRIMARY', '#D71920'),
    ],
];
