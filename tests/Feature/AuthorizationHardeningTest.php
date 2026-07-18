<?php

namespace Tests\Feature;

use App\Models\ExpenseReceipt;
use App\Models\ExpenseRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthorizationHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_unrelated_staff_cannot_download_receipt_and_missing_file_returns_404_without_path_leak(): void
    {
        $this->seed();
        Storage::fake('local');

        [$owner, $other] = $this->staffUsers();
        $receipt = $this->receiptFor($owner, 'receipts/private-owner.pdf');

        $this->actingAs($other)
            ->get(route('receipts.file', $receipt))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('receipts.file', $receipt))
            ->assertNotFound();
    }

    public function test_privileged_receipt_download_is_audited(): void
    {
        $this->seed();
        Storage::fake('local');

        [$owner] = $this->staffUsers();
        $director = User::where('role', 'director_super_admin')->firstOrFail();
        $director->forceFill(['must_change_password' => false])->save();
        $receipt = $this->receiptFor($owner, 'receipts/private-director.pdf');
        Storage::put($receipt->file_path, 'redacted-test-receipt');

        $this->actingAs($director)
            ->get(route('receipts.file', $receipt))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'receipt_downloaded',
            'module' => 'expense_receipts',
            'record_id' => $receipt->id,
            'user_id' => $director->id,
        ]);
    }

    public function test_receipt_update_and_delete_require_record_editability_not_only_visibility(): void
    {
        $this->seed();

        [$owner] = $this->staffUsers();
        $director = User::where('role', 'director_super_admin')->firstOrFail();
        $director->forceFill(['must_change_password' => false])->save();
        $receipt = $this->receiptFor($owner, 'receipts/locked.pdf', 'approved');

        $this->actingAs($director)
            ->patch(route('records.receipts.update', [$receipt->expenseRecord, $receipt]), [
                'document_type' => ExpenseReceipt::DOCUMENT_TYPE_WAZE_SCREENSHOT,
            ])
            ->assertForbidden();

        $this->actingAs($director)
            ->delete(route('records.receipts.destroy', [$receipt->expenseRecord, $receipt]))
            ->assertForbidden();
    }

    public function test_director_cannot_impersonate_inactive_or_password_change_users(): void
    {
        $this->seed();

        $director = User::where('role', 'director_super_admin')->firstOrFail();
        $director->forceFill(['must_change_password' => false])->save();
        [$staff] = $this->staffUsers();
        $inactive = User::factory()->create(['role' => 'staff', 'status' => 'inactive']);
        $mustChange = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => true]);

        $this->actingAs($director)->post(route('admin.impersonate.start', $staff))->assertRedirect(route('dashboard'));
        $this->assertSame($staff->id, session('impersonating_user_id'));

        $this->actingAs($director)->post(route('admin.impersonate.start', $inactive))->assertForbidden();
        $this->actingAs($director)->post(route('admin.impersonate.start', $mustChange))->assertForbidden();
    }

    private function staffUsers(): array
    {
        return [
            User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]),
            User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]),
        ];
    }

    private function receiptFor(User $owner, string $path, string $status = 'draft'): ExpenseReceipt
    {
        $record = ExpenseRecord::create([
            'user_id' => $owner->id,
            'record_type' => ExpenseRecord::TYPE_CLAIMABLE,
            'status' => $status,
            'merchant_name' => 'Test Merchant',
            'receipt_date' => now()->toDateString(),
            'currency' => 'MYR',
            'total_amount' => 10,
        ]);

        return ExpenseReceipt::create([
            'expense_record_id' => $record->id,
            'original_filename' => basename($path),
            'file_path' => $path,
            'file_type' => 'application/pdf',
            'file_size' => 123,
            'uploaded_by' => $owner->id,
            'document_type' => ExpenseReceipt::DOCUMENT_TYPE_RECEIPT,
        ]);
    }
}
