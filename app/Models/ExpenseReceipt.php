<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseReceipt extends Model
{
    public const DOCUMENT_TYPE_RECEIPT = 'receipt';

    public const DOCUMENT_TYPE_WAZE_SCREENSHOT = 'waze_screenshot';

    public const DOCUMENT_TYPE_GOOGLE_MAPS_SCREENSHOT = 'google_maps_screenshot';

    public const DOCUMENT_TYPES = [
        self::DOCUMENT_TYPE_RECEIPT,
        self::DOCUMENT_TYPE_WAZE_SCREENSHOT,
        self::DOCUMENT_TYPE_GOOGLE_MAPS_SCREENSHOT,
    ];

    public const ROUTE_DOCUMENT_TYPES = [
        self::DOCUMENT_TYPE_WAZE_SCREENSHOT,
        self::DOCUMENT_TYPE_GOOGLE_MAPS_SCREENSHOT,
    ];

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

    public static function documentTypes(): array
    {
        return self::DOCUMENT_TYPES;
    }

    public static function normalizeDocumentType(?string $documentType): string
    {
        return in_array($documentType, self::DOCUMENT_TYPES, true)
            ? $documentType
            : self::DOCUMENT_TYPE_RECEIPT;
    }

    public static function isRouteDocumentType(?string $documentType): bool
    {
        return in_array($documentType, self::ROUTE_DOCUMENT_TYPES, true);
    }

    public function url(): string
    {
        return route('receipts.file', $this);
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
        return self::isRouteDocumentType($this->document_type);
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
