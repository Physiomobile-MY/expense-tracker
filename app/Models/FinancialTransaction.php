<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'transactions';

    protected $fillable = [
        'date',
        'type',
        'transaction_category_id',
        'amount',
        'payment_method',
        'reference_number',
        'description',
        'attachment_path',
        'creditor_id',
        'creditor_debt_id',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(TransactionCategory::class, 'transaction_category_id');
    }

    public function creditor()
    {
        return $this->belongsTo(Creditor::class);
    }

    public function debt()
    {
        return $this->belongsTo(CreditorDebt::class, 'creditor_debt_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
