<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReconciliationRecord extends Model
{
    protected $fillable = [
        'bank_transaction_id',
        'transaction_id',
        'status',
        'matched_amount',
        'notes',
    ];

    protected $casts = [
        'matched_amount' => 'decimal:2',
    ];
}
