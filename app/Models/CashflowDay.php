<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashflowDay extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'date',
        'opening_balance',
        'total_inflow',
        'total_outflow',
        'closing_balance',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'opening_balance' => 'decimal:2',
        'total_inflow' => 'decimal:2',
        'total_outflow' => 'decimal:2',
        'closing_balance' => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
