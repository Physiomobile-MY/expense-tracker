<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoaEntry extends Model
{
    protected $fillable = [
        'creditor_id',
        'date',
        'reference',
        'description',
        'debit',
        'credit',
        'running_balance',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'running_balance' => 'decimal:2',
    ];

    public function creditor()
    {
        return $this->belongsTo(Creditor::class);
    }
}
