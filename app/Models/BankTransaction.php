<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    protected $fillable = [
        'bank_import_id',
        'bank_account_id',
        'transaction_date',
        'description',
        'debit',
        'credit',
        'balance',
        'reference',
        'match_status',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2',
    ];
}
