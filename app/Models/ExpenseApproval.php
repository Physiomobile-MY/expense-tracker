<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseApproval extends Model
{
    protected $fillable = [
        'expense_record_id',
        'approver_id',
        'action',
        'previous_status',
        'new_status',
        'remarks',
        'acted_at',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function expenseRecord()
    {
        return $this->belongsTo(ExpenseRecord::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
