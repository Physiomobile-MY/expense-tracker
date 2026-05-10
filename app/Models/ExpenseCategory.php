<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'keywords',
        'status',
    ];

    protected $casts = [
        'keywords' => 'array',
    ];

    public function expenseRecords()
    {
        return $this->hasMany(ExpenseRecord::class);
    }
}
