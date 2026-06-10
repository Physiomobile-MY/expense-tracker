<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditorDebt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'creditor_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'original_amount',
        'paid_amount',
        'outstanding_amount',
        'status',
        'attachment_path',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'original_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
    ];

    public function creditor()
    {
        return $this->belongsTo(Creditor::class);
    }

    public function paymentPlans()
    {
        return $this->hasMany(PaymentPlan::class);
    }
}
