<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Creditor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'creditor_name',
        'company_name',
        'contact_person',
        'phone',
        'email',
        'bank_name',
        'bank_account_number',
        'opening_balance',
        'current_outstanding',
        'priority',
        'relationship_risk',
        'status',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_outstanding' => 'decimal:2',
        'relationship_risk' => 'integer',
    ];

    public function debts()
    {
        return $this->hasMany(CreditorDebt::class);
    }

    public function paymentPlans()
    {
        return $this->hasMany(PaymentPlan::class);
    }

    public function transactions()
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    public function communications()
    {
        return $this->hasMany(CommunicationLog::class);
    }

    public function soaEntries()
    {
        return $this->hasMany(SoaEntry::class);
    }
}
