<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunicationLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'creditor_id',
        'date',
        'type',
        'outcome',
        'promise_date',
        'promise_amount',
        'next_follow_up_date',
        'attachment_path',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'promise_date' => 'date',
        'promise_amount' => 'decimal:2',
        'next_follow_up_date' => 'date',
    ];

    public function creditor()
    {
        return $this->belongsTo(Creditor::class);
    }
}
