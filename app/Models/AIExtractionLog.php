<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIExtractionLog extends Model
{
    protected $table = 'ai_extraction_logs';

    protected $fillable = [
        'expense_record_id',
        'provider',
        'model',
        'prompt',
        'raw_response',
        'extracted_json',
        'confidence_score',
        'status',
        'error_message',
        'token_usage_input',
        'token_usage_output',
        'total_cost_estimate',
    ];

    protected $casts = [
        'extracted_json' => 'array',
        'confidence_score' => 'decimal:4',
        'total_cost_estimate' => 'decimal:6',
    ];

    public function expenseRecord()
    {
        return $this->belongsTo(ExpenseRecord::class);
    }
}
