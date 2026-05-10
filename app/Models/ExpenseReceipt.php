<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ExpenseReceipt extends Model
{
    protected $fillable = [
        'expense_record_id',
        'original_filename',
        'file_path',
        'file_type',
        'file_size',
        'uploaded_by',
        'document_type',
    ];

    public function expenseRecord()
    {
        return $this->belongsTo(ExpenseRecord::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Storage::url($this->file_path);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->file_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->file_type === 'application/pdf';
    }
}
