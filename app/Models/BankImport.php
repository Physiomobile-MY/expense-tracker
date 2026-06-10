<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankImport extends Model
{
    protected $fillable = [
        'bank_account_id',
        'statement_date',
        'bank_provider',
        'file_path',
        'rows_imported',
        'duplicates_skipped',
        'uploaded_by',
    ];

    protected $casts = [
        'statement_date' => 'date',
    ];

    public function account()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }
}
