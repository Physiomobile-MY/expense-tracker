<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'creditor_id',
        'creditor_debt_id',
        'planned_payment_date',
        'planned_amount',
        'priority',
        'status',
        'actual_payment_date',
        'actual_amount_paid',
        'notes',
    ];

    protected $casts = [
        'planned_payment_date' => 'date',
        'actual_payment_date' => 'date',
        'planned_amount' => 'decimal:2',
        'actual_amount_paid' => 'decimal:2',
    ];

    public function creditor()
    {
        return $this->belongsTo(Creditor::class);
    }

    public function debt()
    {
        return $this->belongsTo(CreditorDebt::class, 'creditor_debt_id');
    }
}
