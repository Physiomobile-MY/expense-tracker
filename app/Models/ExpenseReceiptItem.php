<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseReceiptItem extends Model
{
    protected $fillable = [
        'expense_record_id',
        'description',
        'quantity',
        'unit_price',
        'amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function expenseRecord()
    {
        return $this->belongsTo(ExpenseRecord::class);
    }
}
