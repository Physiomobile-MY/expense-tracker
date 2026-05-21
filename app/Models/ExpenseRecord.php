<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseRecord extends Model
{
    use SoftDeletes;

    public const TYPE_CLAIMABLE = 'claimable';

    public const TYPE_NON_CLAIMABLE = 'non_claimable';

    protected $fillable = [
        'user_id',
        'department_id',
        'expense_category_id',
        'claim_reference_no',
        'record_type',
        'claim_expense_type',
        'merchant_name',
        'merchant_address',
        'receipt_date',
        'receipt_time',
        'currency',
        'subtotal',
        'tax_amount',
        'service_charge',
        'discount',
        'total_amount',
        'payment_method',
        'receipt_number',
        'project_cost_center',
        'route_origin',
        'route_destination',
        'route_summary',
        'route_distance_km',
        'route_duration_minutes',
        'route_arrival_time',
        'mileage_rate',
        'mileage_amount',
        'toll_amount',
        'toll_entries',
        'parking_amount',
        'description',
        'remarks',
        'status',
        'duplicate_warning',
        'ai_confidence_score',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'paid_at',
        'recorded_at',
        'reviewed_at',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'receipt_time' => 'datetime:H:i',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'route_distance_km' => 'decimal:2',
        'route_duration_minutes' => 'integer',
        'mileage_rate' => 'decimal:2',
        'mileage_amount' => 'decimal:2',
        'toll_amount' => 'decimal:2',
        'toll_entries' => 'array',
        'parking_amount' => 'decimal:2',
        'duplicate_warning' => 'boolean',
        'ai_confidence_score' => 'decimal:4',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'paid_at' => 'datetime',
        'recorded_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function receipts()
    {
        return $this->hasMany(ExpenseReceipt::class);
    }

    public function primaryReceipt()
    {
        return $this->hasOne(ExpenseReceipt::class)->oldestOfMany();
    }

    public function items()
    {
        return $this->hasMany(ExpenseReceiptItem::class);
    }

    public function aiLogs()
    {
        return $this->hasMany(AIExtractionLog::class);
    }

    public function approvals()
    {
        return $this->hasMany(ExpenseApproval::class);
    }

    public function comments()
    {
        return $this->hasMany(ExpenseComment::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->canManageExpenses()) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }

    public function scopeClaimable(Builder $query): Builder
    {
        return $query->where('record_type', self::TYPE_CLAIMABLE);
    }

    public function scopeNonClaimable(Builder $query): Builder
    {
        return $query->where('record_type', self::TYPE_NON_CLAIMABLE);
    }

    public function scopeWithoutVoided(Builder $query): Builder
    {
        return $query->where($this->getTable().'.status', '!=', 'voided');
    }

    public function statusLabel(): string
    {
        $source = $this->record_type === self::TYPE_NON_CLAIMABLE
            ? config('expenseflow.non_claimable_statuses')
            : config('expenseflow.claimable_statuses');

        return $source[$this->status] ?? str($this->status)->headline()->toString();
    }

    public function recordTypeLabel(): string
    {
        return match ($this->record_type) {
            self::TYPE_CLAIMABLE => 'Claimable',
            self::TYPE_NON_CLAIMABLE => 'Non-Claimable',
            default => 'Draft',
        };
    }

    public function claimExpenseTypeLabel(): string
    {
        return match ($this->claim_expense_type) {
            'mileage' => 'Mileage',
            'toll' => 'Toll',
            'parking' => 'Parking',
            'travel' => 'Travel Claim',
            'receipt' => 'Receipt',
            default => $this->category?->name ?: 'Receipt',
        };
    }

    public function hasTravelClaimDetails(): bool
    {
        return filled($this->route_distance_km)
            || filled($this->mileage_amount)
            || filled($this->toll_amount)
            || filled($this->parking_amount);
    }

    public function hasRouteScreenshot(): bool
    {
        return (bool) $this->routeSourceReceipt()?->isRouteScreenshot();
    }

    public function routeSourceName(): string
    {
        return match ($this->routeSourceReceipt()?->document_type) {
            ExpenseReceipt::DOCUMENT_TYPE_GOOGLE_MAPS_SCREENSHOT => 'Google Maps Route',
            ExpenseReceipt::DOCUMENT_TYPE_WAZE_SCREENSHOT => 'Waze Route',
            default => 'Waze Route',
        };
    }

    private function routeSourceReceipt(): ?ExpenseReceipt
    {
        if ($this->relationLoaded('receipts')) {
            return $this->receipts->first();
        }

        if ($this->relationLoaded('primaryReceipt')) {
            return $this->getRelation('primaryReceipt');
        }

        return $this->primaryReceipt()->first();
    }

    public function canBeEditedBy(User $user): bool
    {
        if ($user->canManageExpenses()) {
            return ! in_array($this->status, ['approved', 'paid', 'reviewed', 'archived', 'voided'], true);
        }

        return $this->user_id === $user->id
            && in_array($this->status, ['draft', 'need_clarification'], true);
    }

    public function canBeReviewedBy(User $user): bool
    {
        return $user->canManageExpenses();
    }

    public function canBeVoidedBy(User $user): bool
    {
        if (in_array($this->status, ['approved', 'paid', 'reviewed', 'archived', 'voided'], true)) {
            return false;
        }

        return $user->canManageExpenses() || $this->user_id === $user->id;
    }
}
