<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ExpenseReceipt extends Model
{
    public const DOCUMENT_TYPE_RECEIPT = 'receipt';

    public const DOCUMENT_TYPE_WAZE_SCREENSHOT = 'waze_screenshot';

    public const DOCUMENT_TYPE_GOOGLE_MAPS_SCREENSHOT = 'google_maps_screenshot';

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

    public function isHeic(): bool
    {
        $filename = str($this->original_filename)->lower()->toString();

        return in_array($this->file_type, ['image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence'], true)
            || str_ends_with($filename, '.heic')
            || str_ends_with($filename, '.heif');
    }

    public function isPreviewableImage(): bool
    {
        return $this->isImage() && ! $this->isHeic();
    }

    public function isPdf(): bool
    {
        return $this->file_type === 'application/pdf';
    }

    public function isWazeScreenshot(): bool
    {
        return $this->document_type === self::DOCUMENT_TYPE_WAZE_SCREENSHOT;
    }

    public function isRouteScreenshot(): bool
    {
        return in_array($this->document_type, [
            self::DOCUMENT_TYPE_WAZE_SCREENSHOT,
            self::DOCUMENT_TYPE_GOOGLE_MAPS_SCREENSHOT,
        ], true);
    }

    public function documentTypeLabel(): string
    {
        return match ($this->document_type) {
            self::DOCUMENT_TYPE_WAZE_SCREENSHOT => 'Waze Screenshot',
            self::DOCUMENT_TYPE_GOOGLE_MAPS_SCREENSHOT => 'Google Maps Screenshot',
            default => 'Receipt',
        };
    }
}
