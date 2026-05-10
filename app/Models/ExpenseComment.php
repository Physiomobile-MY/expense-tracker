<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseComment extends Model
{
    protected $fillable = [
        'expense_record_id',
        'user_id',
        'comment',
    ];

    public function expenseRecord()
    {
        return $this->belongsTo(ExpenseRecord::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
