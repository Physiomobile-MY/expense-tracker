<?php

namespace Tests\Feature;

use App\Mail\ClaimFinanceNotificationMail;
use App\Models\AuditLog;
use App\Models\ExpenseCategory;
use App\Models\ExpenseNotification;
use App\Models\ExpenseReceipt;
use App\Models\ExpenseRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_seeded_user_can_view_dashboard(): void
    {
        $this->withoutVite();
        $this->seed();

        $user = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $user->forceFill(['must_change_password' => false])->save();

        $response = $this->actingAs($user)
            ->get('/');

        $response->assertStatus(200);
    }

    public function test_seeded_directors_must_change_temporary_password(): void
    {
        $this->seed();

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', [
            'email' => 'nidzamyatimi@physiomobile.com',
            'role' => 'director_super_admin',
            'must_change_password' => true,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'saiful@physiomobile.com',
            'role' => 'director_super_admin',
            'must_change_password' => true,
        ]);

        $response = $this->actingAs(User::where('email', 'nidzamyatimi@physiomobile.com')->first())
            ->get('/');

        $response->assertRedirect('/change-password');
    }

    public function test_director_can_upload_receipt_for_manual_review_when_ai_is_not_configured(): void
    {
        $this->seed();
        Storage::fake('local');

        $user = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $user->forceFill(['must_change_password' => false])->save();

        $response = $this->actingAs($user)
            ->post('/upload', [
                'receipt' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('expense_records', 1);
        $this->assertDatabaseCount('expense_receipts', 1);
        $this->assertDatabaseHas('ai_extraction_logs', ['status' => 'failed']);
    }

    public function test_director_can_upload_heic_receipt(): void
    {
        $this->seed();
        Storage::fake('local');

        $user = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $user->forceFill(['must_change_password' => false])->save();

        $response = $this->actingAs($user)
            ->post('/upload', [
                'receipt' => UploadedFile::fake()->create('receipt.heic', 100, 'image/heic'),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('expense_records', 1);
        $this->assertDatabaseHas('expense_receipts', [
            'original_filename' => 'receipt.heic',
        ]);
    }

    public function test_director_can_export_native_xlsx_report(): void
    {
        $this->seed();

        $user = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $user->forceFill(['must_change_password' => false])->save();

        $response = $this->actingAs($user)
            ->get('/reports/export?format=xlsx');

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_claim_submission_emails_finance_for_approval(): void
    {
        $this->seed();
        Mail::fake();

        $user = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $user->forceFill(['must_change_password' => false])->save();
        $category = ExpenseCategory::first();
        $record = ExpenseRecord::create([
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'status' => 'draft',
            'currency' => 'MYR',
        ]);

        $response = $this->actingAs($user)
            ->put('/records/'.$record->id, [
                'intent' => 'claimable',
                'expense_category_id' => $category->id,
                'merchant_name' => 'Test Merchant',
                'receipt_date' => '2026-05-10',
                'currency' => 'MYR',
                'total_amount' => '88.90',
                'description' => 'Test approval notification',
            ]);

        $response->assertRedirect();
        Mail::assertSent(ClaimFinanceNotificationMail::class, function (ClaimFinanceNotificationMail $mail) use ($record): bool {
            return $mail->hasTo('finance.hq@physiomobile.com')
                && $mail->record->id === $record->id
                && $mail->event === 'submitted';
        });
    }

    public function test_claim_submission_auto_fills_category_and_description_when_missing(): void
    {
        $this->seed();
        Mail::fake();

        $user = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $user->forceFill(['must_change_password' => false])->save();
        $record = ExpenseRecord::create([
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'status' => 'draft',
            'currency' => 'MYR',
        ]);

        $response = $this->actingAs($user)
            ->put('/records/'.$record->id, [
                'intent' => 'claimable',
                'merchant_name' => 'Hayaki Kopitiam',
                'receipt_date' => '2026-05-10',
                'currency' => 'MYR',
                'total_amount' => '18.90',
                'items' => [
                    ['description' => 'Set Nasi Ayam Madu', 'quantity' => 1, 'unit_price' => 18.90, 'amount' => 18.90],
                ],
            ]);

        $response->assertRedirect();
        $record->refresh();

        $this->assertSame('pending_review', $record->status);
        $this->assertSame('Meal', $record->category?->name);
        $this->assertSame('Receipt from Hayaki Kopitiam', $record->description);
    }

    public function test_claim_approval_emails_finance_for_payment_follow_up(): void
    {
        $this->seed();
        Mail::fake();

        $user = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $user->forceFill(['must_change_password' => false])->save();
        $category = ExpenseCategory::first();
        $record = ExpenseRecord::create([
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'expense_category_id' => $category->id,
            'claim_reference_no' => 'PMEXP-202605-00001',
            'record_type' => ExpenseRecord::TYPE_CLAIMABLE,
            'merchant_name' => 'Approved Merchant',
            'receipt_date' => '2026-05-10',
            'currency' => 'MYR',
            'total_amount' => '120.00',
            'description' => 'Approved claim',
            'status' => 'pending_review',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->post('/records/'.$record->id.'/approve', [
                'remarks' => 'Approved for payment',
            ]);

        $response->assertRedirect();
        Mail::assertSent(ClaimFinanceNotificationMail::class, function (ClaimFinanceNotificationMail $mail) use ($record): bool {
            return $mail->hasTo('finance.hq@physiomobile.com')
                && $mail->record->id === $record->id
                && $mail->event === 'approved'
                && $mail->remarks === 'Approved for payment';
        });
    }

    public function test_clear_records_command_deletes_expense_test_data_only(): void
    {
        $this->seed();
        Storage::fake('local');

        $user = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $path = 'receipts/2026/05/test.pdf';
        Storage::put($path, 'receipt');

        $record = ExpenseRecord::create([
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'claim_reference_no' => 'PMEXP-202605-00002',
            'record_type' => ExpenseRecord::TYPE_CLAIMABLE,
            'status' => 'pending_review',
            'currency' => 'MYR',
            'total_amount' => '50.00',
        ]);
        ExpenseReceipt::create([
            'expense_record_id' => $record->id,
            'original_filename' => 'test.pdf',
            'file_path' => $path,
            'file_type' => 'application/pdf',
            'file_size' => 100,
            'uploaded_by' => $user->id,
        ]);
        ExpenseNotification::create([
            'user_id' => $user->id,
            'title' => 'Test',
            'message' => 'Test',
            'type' => 'test',
        ]);
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'test',
            'module' => 'expense_records',
            'record_id' => $record->id,
        ]);

        $this->artisan('expenseflow:clear-records', ['--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('expense_records', 0);
        $this->assertDatabaseCount('expense_receipts', 0);
        $this->assertDatabaseCount('expense_notifications', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        Storage::assertMissing($path);
    }

    public function test_user_can_void_own_unapproved_claim_with_reason(): void
    {
        $this->seed();

        $user = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $user->forceFill(['must_change_password' => false])->save();
        $category = ExpenseCategory::first();
        $record = ExpenseRecord::create([
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'expense_category_id' => $category->id,
            'claim_reference_no' => 'PMEXP-202605-00003',
            'record_type' => ExpenseRecord::TYPE_CLAIMABLE,
            'merchant_name' => 'Duplicate Merchant',
            'receipt_date' => '2026-05-10',
            'currency' => 'MYR',
            'total_amount' => '33.00',
            'description' => 'Duplicate upload',
            'status' => 'pending_review',
        ]);

        $response = $this->actingAs($user)
            ->post('/records/'.$record->id.'/void', [
                'reason' => 'Uploaded same receipt twice.',
            ]);

        $response->assertRedirect('/records/'.$record->id);
        $this->assertDatabaseHas('expense_records', [
            'id' => $record->id,
            'status' => 'voided',
        ]);
        $this->assertDatabaseHas('expense_approvals', [
            'expense_record_id' => $record->id,
            'action' => 'voided',
            'remarks' => 'Uploaded same receipt twice.',
        ]);
        $this->assertDatabaseHas('expense_comments', [
            'expense_record_id' => $record->id,
            'comment' => 'Void reason: Uploaded same receipt twice.',
        ]);
    }

    public function test_ensure_catalog_command_creates_default_categories(): void
    {
        $this->artisan('expenseflow:ensure-catalog')
            ->assertSuccessful();

        $this->assertDatabaseHas('expense_categories', [
            'code' => 'MEAL',
            'name' => 'Meal',
            'status' => 'active',
        ]);
        $this->assertContains('kopitiam', ExpenseCategory::where('code', 'MEAL')->first()->keywords);
        $this->assertDatabaseHas('expense_categories', [
            'code' => 'OTHERS',
            'name' => 'Others',
            'status' => 'active',
        ]);
    }

    public function test_category_keywords_can_be_configured_and_used_for_auto_matching(): void
    {
        $this->seed();
        Mail::fake();

        $user = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $user->forceFill(['must_change_password' => false])->save();

        $this->actingAs($user)
            ->post('/admin/categories', [
                'name' => 'Refreshments',
                'code' => 'REFRESHMENTS',
                'description' => 'Drinks and light refreshments',
                'keywords_text' => "teh tarik\nminuman",
                'status' => 'active',
            ])
            ->assertRedirect();

        $record = ExpenseRecord::create([
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'status' => 'draft',
            'currency' => 'MYR',
        ]);

        $this->actingAs($user)
            ->put('/records/'.$record->id, [
                'intent' => 'claimable',
                'merchant_name' => 'Teh Tarik Station',
                'receipt_date' => '2026-05-10',
                'currency' => 'MYR',
                'total_amount' => '6.50',
            ])
            ->assertRedirect();

        $record->refresh();

        $this->assertSame('Refreshments', $record->category?->name);
    }

    public function test_finance_email_test_command_sends_to_configured_recipient(): void
    {
        config(['expenseflow.notifications.finance_approval_email' => 'finance.hq@physiomobile.com']);

        $this->artisan('expenseflow:test-finance-email')
            ->expectsOutputToContain('To: finance.hq@physiomobile.com')
            ->expectsOutputToContain('Test email sent.')
            ->assertSuccessful();
    }
}
