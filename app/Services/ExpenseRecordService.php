<?php

namespace App\Services;

use App\Mail\ClaimFinanceNotificationMail;
use App\Models\AuditLog;
use App\Models\ExpenseApproval;
use App\Models\ExpenseCategory;
use App\Models\ExpenseNotification;
use App\Models\ExpenseReceipt;
use App\Models\ExpenseRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ExpenseRecordService
{
    public function __construct(
        private readonly DuplicateReceiptService $duplicateReceiptService,
    ) {}

    public function createDraftFromUpload(User $user, UploadedFile $file): ExpenseRecord
    {
        return DB::transaction(function () use ($user, $file): ExpenseRecord {
            $record = ExpenseRecord::create([
                'user_id' => $user->id,
                'department_id' => $user->department_id,
                'status' => 'draft',
                'currency' => 'MYR',
            ]);

            $path = Storage::putFile('receipts/'.now()->format('Y/m'), $file);

            ExpenseReceipt::create([
                'expense_record_id' => $record->id,
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getMimeType() ?: $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => $user->id,
            ]);

            $this->audit($user, 'receipt_uploaded', 'expense_records', $record->id, null, [
                'filename' => $file->getClientOriginalName(),
            ]);

            return $record->refresh();
        });
    }

    public function applyExtraction(ExpenseRecord $record, array $data): void
    {
        $record->fill([
            'merchant_name' => $data['merchant_name'] ?? $record->merchant_name,
            'merchant_address' => $data['merchant_address'] ?? $record->merchant_address,
            'receipt_date' => $data['receipt_date'] ?? $record->receipt_date,
            'receipt_time' => $this->normalizeTime($data['receipt_time'] ?? null) ?? $record->receipt_time,
            'currency' => $data['currency'] ?? $record->currency ?? 'MYR',
            'subtotal' => $data['subtotal'] ?? $record->subtotal,
            'tax_amount' => $data['tax_amount'] ?? $record->tax_amount,
            'service_charge' => $data['service_charge'] ?? $record->service_charge,
            'discount' => $data['discount'] ?? $record->discount,
            'total_amount' => $data['total_amount'] ?? $record->total_amount,
            'payment_method' => $data['payment_method'] ?? $record->payment_method,
            'receipt_number' => $data['receipt_number'] ?? $record->receipt_number,
            'ai_confidence_score' => $data['confidence_score'] ?? $record->ai_confidence_score,
            'remarks' => $data['notes'] ?? $record->remarks,
        ])->save();

        if (! $record->expense_category_id) {
            $record->forceFill([
                'expense_category_id' => $this->inferCategoryId($record, $data),
            ])->save();
        }

        $record->items()->delete();
        foreach ((array) ($data['items'] ?? []) as $item) {
            if (! array_filter((array) $item)) {
                continue;
            }

            $record->items()->create([
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'] ?? null,
                'unit_price' => $item['unit_price'] ?? null,
                'amount' => $item['amount'] ?? null,
            ]);
        }
    }

    public function updateDraft(ExpenseRecord $record, User $actor, array $data): ExpenseRecord
    {
        $this->ensureEditable($record, $actor);

        $before = $record->only(array_keys($this->recordPayloadWithFallbacks($record, $data)));

        return DB::transaction(function () use ($record, $actor, $data, $before): ExpenseRecord {
            $record->fill($this->recordPayloadWithFallbacks($record, $data))->save();
            $this->syncItems($record, $data['items'] ?? []);
            $this->duplicateReceiptService->refreshWarning($record);
            $this->audit($actor, 'record_updated', 'expense_records', $record->id, $before, $record->fresh()->toArray());

            return $record->refresh();
        });
    }

    public function submit(ExpenseRecord $record, User $actor, array $data): ExpenseRecord
    {
        $this->ensureEditable($record, $actor);

        $submittedRecord = DB::transaction(function () use ($record, $actor, $data): ExpenseRecord {
            $record->fill($this->recordPayloadWithFallbacks($record, $data));
            $record->record_type = $data['record_type'];
            $record->department_id = $record->department_id ?: $actor->department_id;

            if (! $record->claim_reference_no) {
                $record->claim_reference_no = $this->generateReferenceNo($record->record_type, $record->receipt_date);
            }

            if ($record->record_type === ExpenseRecord::TYPE_CLAIMABLE) {
                $record->status = 'pending_review';
                $record->submitted_at = now();
            } else {
                $record->status = 'recorded';
                $record->recorded_at = now();
            }

            $record->save();
            $this->syncItems($record, $data['items'] ?? []);
            $duplicate = $this->duplicateReceiptService->refreshWarning($record);

            if ($record->record_type === ExpenseRecord::TYPE_CLAIMABLE) {
                $this->notifyManagers(
                    'Claim pending review',
                    $record->claim_reference_no.' is ready for review.',
                    'claim_submitted'
                );
            } else {
                $this->notifyManagers(
                    'Non-claimable receipt recorded',
                    $record->claim_reference_no.' was saved as a company record.',
                    'receipt_recorded'
                );
            }

            if ($duplicate) {
                $this->notifyManagers(
                    'Possible duplicate receipt',
                    $record->claim_reference_no.' may duplicate an existing receipt.',
                    'duplicate_warning'
                );
            }

            $this->audit($actor, 'record_submitted', 'expense_records', $record->id, null, [
                'record_type' => $record->record_type,
                'status' => $record->status,
            ]);

            return $record->refresh();
        });

        if ($submittedRecord->record_type === ExpenseRecord::TYPE_CLAIMABLE) {
            $this->sendFinanceClaimEmail($submittedRecord, 'submitted');
        }

        return $submittedRecord;
    }

    public function approve(ExpenseRecord $record, User $actor, ?string $remarks = null): ExpenseRecord
    {
        $this->ensureReviewer($record, $actor);

        if ($record->record_type !== ExpenseRecord::TYPE_CLAIMABLE || ! in_array($record->status, ['submitted', 'pending_review', 'need_clarification'], true)) {
            throw ValidationException::withMessages(['status' => 'Only pending claimable records can be approved.']);
        }

        $approvedRecord = $this->transition($record, $actor, 'approved', 'approved', $remarks, ['approved_at' => now()]);

        $this->sendFinanceClaimEmail($approvedRecord, 'approved', $actor, $remarks);

        return $approvedRecord;
    }

    public function reject(ExpenseRecord $record, User $actor, ?string $remarks = null): ExpenseRecord
    {
        $this->ensureReviewer($record, $actor);

        if ($record->record_type !== ExpenseRecord::TYPE_CLAIMABLE || ! in_array($record->status, ['submitted', 'pending_review', 'need_clarification'], true)) {
            throw ValidationException::withMessages(['status' => 'Only pending claimable records can be rejected.']);
        }

        return $this->transition($record, $actor, 'rejected', 'rejected', $remarks, ['rejected_at' => now()]);
    }

    public function voidRecord(ExpenseRecord $record, User $actor, string $reason): ExpenseRecord
    {
        if (! $record->canBeVoidedBy($actor)) {
            throw ValidationException::withMessages(['record' => 'This expense record cannot be voided in its current status.']);
        }

        $voidedRecord = $this->transition($record, $actor, 'voided', 'voided', $reason);
        $voidedRecord->forceFill(['duplicate_warning' => false])->save();
        $voidedRecord->comments()->create([
            'user_id' => $actor->id,
            'comment' => 'Void reason: '.$reason,
        ]);

        return $voidedRecord->refresh();
    }

    public function requestClarification(ExpenseRecord $record, User $actor, string $remarks): ExpenseRecord
    {
        $this->ensureReviewer($record, $actor);

        if ($record->record_type !== ExpenseRecord::TYPE_CLAIMABLE || ! in_array($record->status, ['submitted', 'pending_review', 'need_clarification'], true)) {
            throw ValidationException::withMessages(['status' => 'Only pending claimable records can request clarification.']);
        }

        $record = $this->transition($record, $actor, 'clarification_requested', 'need_clarification', $remarks);
        $record->comments()->create([
            'user_id' => $actor->id,
            'comment' => $remarks,
        ]);

        return $record;
    }

    public function markPaid(ExpenseRecord $record, User $actor, ?string $remarks = null): ExpenseRecord
    {
        $this->ensureReviewer($record, $actor);

        if ($record->record_type !== ExpenseRecord::TYPE_CLAIMABLE || $record->status !== 'approved') {
            throw ValidationException::withMessages(['status' => 'Only approved claimable records can be marked as paid.']);
        }

        return $this->transition($record, $actor, 'paid', 'paid', $remarks, ['paid_at' => now()]);
    }

    public function reviewNonClaimable(ExpenseRecord $record, User $actor, ?string $remarks = null): ExpenseRecord
    {
        $this->ensureReviewer($record, $actor);

        if ($record->record_type !== ExpenseRecord::TYPE_NON_CLAIMABLE || ! in_array($record->status, ['recorded', 'flagged'], true)) {
            throw ValidationException::withMessages(['status' => 'Only recorded non-claimable receipts can be reviewed.']);
        }

        return $this->transition($record, $actor, 'reviewed', 'reviewed', $remarks, ['reviewed_at' => now()]);
    }

    public function flagNonClaimable(ExpenseRecord $record, User $actor, ?string $remarks = null): ExpenseRecord
    {
        $this->ensureReviewer($record, $actor);

        if ($record->record_type !== ExpenseRecord::TYPE_NON_CLAIMABLE || ! in_array($record->status, ['recorded', 'reviewed'], true)) {
            throw ValidationException::withMessages(['status' => 'Only recorded or reviewed non-claimable receipts can be flagged.']);
        }

        return $this->transition($record, $actor, 'flagged', 'flagged', $remarks);
    }

    public function generateReferenceNo(string $recordType, $receiptDate = null): string
    {
        $prefix = $recordType === ExpenseRecord::TYPE_NON_CLAIMABLE ? 'PMREC' : 'PMEXP';
        $date = $receiptDate ? Carbon::parse($receiptDate) : now();
        $monthPrefix = $prefix.'-'.$date->format('Ym').'-';
        $latest = ExpenseRecord::query()
            ->where('claim_reference_no', 'like', $monthPrefix.'%')
            ->orderByDesc('claim_reference_no')
            ->value('claim_reference_no');

        $next = $latest ? ((int) substr($latest, -5)) + 1 : 1;

        return $monthPrefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    private function transition(ExpenseRecord $record, User $actor, string $action, string $newStatus, ?string $remarks = null, array $timestamps = []): ExpenseRecord
    {
        return DB::transaction(function () use ($record, $actor, $action, $newStatus, $remarks, $timestamps): ExpenseRecord {
            $previousStatus = $record->status;
            $record->forceFill(array_merge(['status' => $newStatus], $timestamps))->save();

            ExpenseApproval::create([
                'expense_record_id' => $record->id,
                'approver_id' => $actor->id,
                'action' => $action,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'remarks' => $remarks,
                'acted_at' => now(),
            ]);

            $this->notifyUser($record->user, $this->notificationTitle($action), $this->notificationMessage($record, $action), $action);
            $this->audit($actor, $action, 'expense_records', $record->id, ['status' => $previousStatus], ['status' => $newStatus]);

            return $record->refresh();
        });
    }

    private function ensureEditable(ExpenseRecord $record, User $actor): void
    {
        if (! $record->canBeEditedBy($actor)) {
            throw ValidationException::withMessages(['record' => 'This expense record cannot be edited in its current status.']);
        }
    }

    private function ensureReviewer(ExpenseRecord $record, User $actor): void
    {
        if (! $record->canBeReviewedBy($actor)) {
            throw ValidationException::withMessages(['record' => 'You do not have permission to review this expense record.']);
        }
    }

    private function recordPayload(array $data): array
    {
        return Arr::only($data, [
            'department_id',
            'expense_category_id',
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
            'description',
            'remarks',
        ]);
    }

    private function recordPayloadWithFallbacks(ExpenseRecord $record, array $data): array
    {
        $payload = $this->recordPayload($data);

        if (empty($payload['expense_category_id'])) {
            $payload['expense_category_id'] = $record->expense_category_id ?: $this->inferCategoryId($record, $data);
        }

        if (blank($payload['description'] ?? null)) {
            $merchant = $payload['merchant_name'] ?? $record->merchant_name;
            $payload['description'] = $merchant ? 'Receipt from '.$merchant : 'Receipt uploaded for expense record.';
        }

        return $payload;
    }

    private function inferCategoryId(ExpenseRecord $record, array $data): int
    {
        $text = str($record->merchant_name.' '.($data['merchant_name'] ?? '').' '.($data['description'] ?? ''))
            ->append(' '.collect($data['items'] ?? [])->pluck('description')->implode(' '))
            ->lower()
            ->toString();

        foreach (ExpenseCategory::query()->where('status', 'active')->orderBy('name')->get() as $category) {
            if ($category->code === 'OTHERS') {
                continue;
            }

            foreach ($this->categoryKeywords($category) as $keyword) {
                if ($keyword !== '' && str_contains($text, $keyword)) {
                    return $category->id;
                }
            }
        }

        return $this->ensureCategory('Others')->id;
    }

    private function ensureCategory(string $name): ExpenseCategory
    {
        $code = str($name)->upper()->replaceMatches('/[^A-Z0-9]+/', '_')->trim('_')->toString();
        $keywords = config('expenseflow.category_keywords.'.$name, []);

        return tap(ExpenseCategory::firstOrCreate(
            ['code' => $code],
            ['name' => $name, 'description' => null, 'keywords' => $keywords, 'status' => 'active']
        ), function (ExpenseCategory $category) use ($name, $keywords): void {
            if ($category->status !== 'active' || $category->name !== $name) {
                $category->forceFill(['name' => $name, 'status' => 'active'])->save();
            }

            if (blank($category->keywords) && filled($keywords)) {
                $category->forceFill(['keywords' => $keywords])->save();
            }
        });
    }

    private function categoryKeywords(ExpenseCategory $category): array
    {
        $keywords = $category->keywords ?: config('expenseflow.category_keywords.'.$category->name, []);

        return collect($keywords)
            ->map(fn ($keyword): string => str((string) $keyword)->lower()->trim()->toString())
            ->filter()
            ->values()
            ->all();
    }

    private function syncItems(ExpenseRecord $record, array $items): void
    {
        $record->items()->delete();

        foreach ($items as $item) {
            if (! array_filter((array) $item)) {
                continue;
            }

            $record->items()->create([
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'] ?? null,
                'unit_price' => $item['unit_price'] ?? null,
                'amount' => $item['amount'] ?? null,
            ]);
        }
    }

    private function normalizeTime(?string $time): ?string
    {
        if (! $time) {
            return null;
        }

        return substr($time, 0, 5);
    }

    private function notifyManagers(string $title, string $message, string $type): void
    {
        User::query()
            ->whereIn('role', ['director_super_admin', 'admin_finance'])
            ->where('status', 'active')
            ->each(fn (User $user) => $this->notifyUser($user, $title, $message, $type));
    }

    private function notifyUser(User $user, string $title, string $message, string $type): void
    {
        ExpenseNotification::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
        ]);
    }

    private function sendFinanceClaimEmail(ExpenseRecord $record, string $event, ?User $actor = null, ?string $remarks = null): void
    {
        $email = config('expenseflow.notifications.finance_approval_email');

        if (! $email) {
            return;
        }

        try {
            Mail::to($email)->send(new ClaimFinanceNotificationMail($record, $event, $actor, $remarks));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function notificationTitle(string $action): string
    {
        return match ($action) {
            'approved' => 'Claim approved',
            'rejected' => 'Claim rejected',
            'clarification_requested' => 'Clarification requested',
            'paid' => 'Claim marked as paid',
            'reviewed' => 'Receipt reviewed',
            'flagged' => 'Receipt flagged',
            'voided' => 'Claim voided',
            default => 'Expense record updated',
        };
    }

    private function notificationMessage(ExpenseRecord $record, string $action): string
    {
        return match ($action) {
            'approved' => $record->claim_reference_no.' was approved.',
            'rejected' => $record->claim_reference_no.' was rejected.',
            'clarification_requested' => 'Please respond to the clarification request for '.$record->claim_reference_no.'.',
            'paid' => $record->claim_reference_no.' has been marked as paid.',
            'reviewed' => $record->claim_reference_no.' was reviewed.',
            'flagged' => $record->claim_reference_no.' was flagged for review.',
            'voided' => ($record->claim_reference_no ?: 'Draft receipt').' was voided.',
            default => $record->claim_reference_no.' was updated.',
        };
    }

    private function audit(?User $actor, string $action, string $module, ?int $recordId, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
