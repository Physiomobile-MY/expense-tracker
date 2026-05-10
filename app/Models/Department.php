<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'name',
        'code',
        'status',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function expenseRecords()
    {
        return $this->hasMany(ExpenseRecord::class);
    }
}
