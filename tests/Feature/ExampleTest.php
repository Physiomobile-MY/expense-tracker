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
use Spatie\Permission\Models\Role;
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
        $this->assertDatabaseHas('roles', [
            'name' => 'executive',
        ]);

        $response = $this->actingAs(User::where('email', 'nidzamyatimi@physiomobile.com')->first())
            ->get('/');

        $response->assertRedirect('/change-password');
    }

    public function test_director_can_create_executive_user_from_admin(): void
    {
        $this->seed();

        $director = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $director->forceFill(['must_change_password' => false])->save();

        Role::where('name', 'executive')->delete();

        $this->actingAs($director)
            ->post('/admin/users', [
                'name' => 'Nidzam Executive',
                'email' => 'nidzam.executive@physiomobile.com',
                'phone' => null,
                'department_id' => null,
                'role' => 'executive',
                'status' => 'active',
                'password' => 'password',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'name' => 'Nidzam Executive',
            'email' => 'nidzam.executive@physiomobile.com',
            'role' => 'executive',
            'status' => 'active',
        ]);
        $this->assertTrue(User::where('email', 'nidzam.executive@physiomobile.com')->first()->hasRole('executive'));
    }

    public function test_executive_accounts_are_staff_level_and_only_see_their_own_records(): void
    {
        $this->withoutVite();
        $this->seed();

        $executive = User::factory()->create([
            'name' => 'Nidzam Executive',
            'email' => 'nidzam.executive@physiomobile.com',
            'role' => 'executive',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $otherExecutive = User::factory()->create([
            'name' => 'Saiful Executive',
            'email' => 'saiful.executive@physiomobile.com',
            'role' => 'executive',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $executive->syncRoles(['executive']);
        $otherExecutive->syncRoles(['executive']);
        $category = ExpenseCategory::first();

        $ownRecord = ExpenseRecord::create([
            'user_id' => $executive->id,
            'department_id' => $executive->department_id,
            'expense_category_id' => $category->id,
            'claim_reference_no' => 'PMEXP-202605-01001',
            'record_type' => ExpenseRecord::TYPE_CLAIMABLE,
            'merchant_name' => 'Own Executive Merchant',
            'receipt_date' => '2026-05-10',
            'currency' => 'MYR',
            'total_amount' => '42.00',
            'status' => 'pending_review',
        ]);
        $otherRecord = ExpenseRecord::create([
            'user_id' => $otherExecutive->id,
            'department_id' => $otherExecutive->department_id,
            'expense_category_id' => $category->id,
            'claim_reference_no' => 'PMEXP-202605-01002',
            'record_type' => ExpenseRecord::TYPE_CLAIMABLE,
            'merchant_name' => 'Other Executive Merchant',
            'receipt_date' => '2026-05-10',
            'currency' => 'MYR',
            'total_amount' => '84.00',
            'status' => 'pending_review',
        ]);

        $this->assertTrue($executive->isStaffLevel());
        $this->assertFalse($executive->canManageExpenses());

        $this->actingAs($executive)
            ->get('/')
            ->assertOk()
            ->assertSee('My Dashboard')
            ->assertSee('Executive')
            ->assertSee('Own Executive Merchant')
            ->assertDontSee('Other Executive Merchant')
            ->assertSee('Upload Claim')
            ->assertDontSee('Reports');

        $this->actingAs($executive)
            ->get('/records')
            ->assertOk()
            ->assertSee($ownRecord->claim_reference_no)
            ->assertSee('Own Executive Merchant')
            ->assertDontSee($otherRecord->claim_reference_no)
            ->assertDontSee('Other Executive Merchant');

        $this->actingAs($executive)
            ->get('/records/'.$otherRecord->id)
            ->assertForbidden();

        $this->actingAs($executive)
            ->get('/reports')
            ->assertForbidden();
    }

    public function test_director_upload_is_submitted_for_review_when_ai_is_not_configured(): void
    {
        $this->seed();
        Storage::fake('local');
        Mail::fake();

        $user = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $user->forceFill(['must_change_password' => false])->save();

        $response = $this->actingAs($user)
            ->post('/upload', [
                'document_type' => 'receipt',
                'receipts' => [
                    UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('expense_records', 1);
        $this->assertDatabaseCount('expense_receipts', 1);
        $receipt = ExpenseReceipt::first();
        $this->assertStringStartsWith('receipts/', $receipt->file_path);
        $this->assertDatabaseHas('ai_extraction_logs', ['status' => 'failed']);
        $record = ExpenseRecord::first();
        $this->assertSame('pending_review', $record->status);
        $this->assertSame(ExpenseRecord::TYPE_CLAIMABLE, $record->record_type);
        $this->assertNotNull($record->claim_reference_no);
        $this->assertNotNull($record->submitted_at);
    }

    public function test_director_can_upload_heic_receipt(): void
    {
        $this->seed();
        Storage::fake('local');
        Mail::fake();

        $user = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $user->forceFill(['must_change_password' => false])->save();

        $response = $this->actingAs($user)
            ->post('/upload', [
                'document_type' => 'receipt',
                'receipts' => [
                    UploadedFile::fake()->create('receipt.heic', 100, 'image/heic'),
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('expense_records', 1);
        $this->assertDatabaseHas('expense_receipts', [
            'original_filename' => 'receipt.heic',
        ]);
        $this->assertDatabaseHas('expense_records', [
            'status' => 'pending_review',
            'record_type' => ExpenseRecord::TYPE_CLAIMABLE,
        ]);
    }

    public function test_both_seeded_directors_upload_receipts_as_pending_review(): void
    {
        $this->seed();
        Storage::fake('local');
        Mail::fake();

        foreach (['nidzamyatimi@physiomobile.com', 'saiful@physiomobile.com'] as $index => $email) {
            $user = User::where('email', $email)->firstOrFail();
            $user->forceFill(['must_change_password' => false])->save();

            $this->actingAs($user)
                ->post('/upload', [
                    'document_type' => 'receipt',
                    'receipts' => [
                        UploadedFile::fake()->create('receipt-'.($index + 1).'.pdf', 100, 'application/pdf'),
                    ],
                ])
                ->assertRedirect();

            $record = ExpenseRecord::where('user_id', $user->id)->latest('id')->firstOrFail();

            $this->assertSame('pending_review', $record->status);
            $this->assertSame(ExpenseRecord::TYPE_CLAIMABLE, $record->record_type);
            $this->assertNotNull($record->submitted_at);
        }
    }

    public function test_waze_mileage_claim_calculates_mileage_toll_and_parking_total(): void
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
            'claim_expense_type' => 'mileage',
        ]);

        $this->actingAs($user)
            ->put('/records/'.$record->id, [
                'intent' => 'claimable',
                'claim_expense_type' => 'mileage',
                'receipt_date' => '2026-05-20',
                'currency' => 'MYR',
                'route_origin' => 'Shah Alam',
                'route_destination' => 'Klinik Ehsan Bandar Sri Permaisuri',
                'route_summary' => 'Via E39 Lebuhraya SPE',
                'route_distance_km' => '15',
                'mileage_rate' => '0.50',
                'toll_entries' => [
                    ['label' => 'SPE Toll', 'amount' => '1.00'],
                    ['label' => 'Kajang Toll', 'amount' => '1.50'],
                ],
                'parking_amount' => '5.00',
                'total_amount' => '1.00',
            ])
            ->assertRedirect();

        $record->refresh();

        $this->assertSame('pending_review', $record->status);
        $this->assertSame('Mileage', $record->category?->name);
        $this->assertSame('Waze Route', $record->merchant_name);
        $this->assertSame('7.50', (string) $record->mileage_amount);
        $this->assertSame('2.50', (string) $record->toll_amount);
        $this->assertSame('SPE Toll', $record->toll_entries[0]['label']);
        $this->assertSame('15.00', (string) $record->total_amount);
        $this->assertSame('Mileage claim to Klinik Ehsan Bandar Sri Permaisuri', $record->description);
    }

    public function test_google_maps_screenshot_upload_creates_pending_route_claim(): void
    {
        $this->withoutVite();
        $this->seed();
        Storage::fake('local');

        $user = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $user->forceFill(['must_change_password' => false])->save();

        $this->actingAs($user)
            ->post('/upload', [
                'document_type' => 'google_maps_screenshot',
                'receipts' => [
                    UploadedFile::fake()->image('google-maps-route.jpg'),
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('expense_records', [
            'claim_expense_type' => 'mileage',
            'merchant_name' => 'Google Maps Route',
        ]);
        $this->assertDatabaseHas('expense_receipts', [
            'document_type' => 'google_maps_screenshot',
            'original_filename' => 'google-maps-route.jpg',
        ]);

        $record = ExpenseRecord::with('receipts')->first();
        $receipt = $record->receipts->first();

        $this->assertStringStartsWith('route-screenshots/', $receipt->file_path);

        $this->actingAs($user)
            ->put('/records/'.$record->id, [
                'intent' => 'save',
                'claim_expense_type' => 'mileage',
                'merchant_name' => 'Waze Route',
                'receipt_date' => '2026-05-21',
                'currency' => 'MYR',
                'route_distance_km' => '10',
                'mileage_rate' => '0.50',
                'parking_amount' => '2.00',
            ])
            ->assertRedirect();

        $record->refresh();

        $this->assertSame('Google Maps Route', $record->merchant_name);
        $this->assertSame('7.00', (string) $record->subtotal);

        $this->actingAs($user)
            ->get('/records/'.$record->id.'/edit')
            ->assertOk()
            ->assertSee('Journey Details')
            ->assertSee('Journey date')
            ->assertSee('Journey time')
            ->assertSee('Subtotal (mileage + toll + parking)')
            ->assertDontSee('Receipt Items')
            ->assertDontSee('Merchant address')
            ->assertDontSee('Receipt number');
    }

    public function test_record_edit_form_keeps_submit_buttons_inside_main_form_when_receipts_have_actions(): void
    {
        $this->withoutVite();
        $this->seed();

        $user = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $user->forceFill(['must_change_password' => false])->save();

        $record = ExpenseRecord::create([
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'expense_category_id' => ExpenseCategory::first()->id,
            'claim_reference_no' => 'PMEXP-202605-01301',
            'record_type' => ExpenseRecord::TYPE_CLAIMABLE,
            'merchant_name' => 'Hayaki Kopitiam',
            'receipt_date' => '2026-05-21',
            'currency' => 'MYR',
            'total_amount' => '12.50',
            'status' => 'draft',
        ]);

        $receipt = ExpenseReceipt::create([
            'expense_record_id' => $record->id,
            'original_filename' => 'receipt.jpg',
            'file_path' => 'receipts/receipt.jpg',
            'file_type' => 'image/jpeg',
            'file_size' => 1200,
            'uploaded_by' => $user->id,
            'document_type' => ExpenseReceipt::DOCUMENT_TYPE_RECEIPT,
        ]);

        $response = $this->actingAs($user)
            ->get('/records/'.$record->id.'/edit')
            ->assertOk();

        $html = $response->getContent();
        $this->assertStringContainsString('name="intent" value="claimable"', $html);
        $this->assertStringContainsString('name="intent_override" value="save"', $html);
        $this->assertStringContainsString('name="intent_override" value="non_claimable"', $html);
        $this->assertStringContainsString('form="receipt-update-form-'.$receipt->id.'"', $html);
        $this->assertStringContainsString('id="receipt-update-form-'.$receipt->id.'"', $html);

        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        $this->assertSame(0, $xpath->query('//form//form')->length);
        $claimableButtonForm = $xpath->query('//input[@name="intent" and @value="claimable"]/ancestor::form[1]');

        $this->assertSame(1, $claimableButtonForm->length);
        $this->assertStringContainsString('/records/'.$record->id, $claimableButtonForm->item(0)->getAttribute('action'));
    }

    public function test_both_seeded_directors_submit_claims_as_pending_review(): void
    {
        $this->seed();
        Mail::fake();

        foreach (['nidzamyatimi@physiomobile.com', 'saiful@physiomobile.com'] as $index => $email) {
            $user = User::where('email', $email)->firstOrFail();
            $user->forceFill(['must_change_password' => false])->save();

            $record = ExpenseRecord::create([
                'user_id' => $user->id,
                'department_id' => $user->department_id,
                'status' => 'draft',
                'currency' => 'MYR',
            ]);

            $this->actingAs($user)
                ->put('/records/'.$record->id, [
                    'intent' => 'claimable',
                    'merchant_name' => 'Submission Test '.($index + 1),
                    'receipt_date' => '2026-07-10',
                    'currency' => 'MYR',
                    'total_amount' => '10.00',
                ])
                ->assertRedirect('/records/'.$record->id);

            $record->refresh();

            $this->assertSame('pending_review', $record->status);
            $this->assertSame(ExpenseRecord::TYPE_CLAIMABLE, $record->record_type);
            $this->assertNotNull($record->submitted_at);
        }
    }

    public function test_route_screenshot_can_be_attached_to_existing_draft(): void
    {
        $this->seed();
        Storage::fake('local');

        $user = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $user->forceFill(['must_change_password' => false])->save();

        $record = ExpenseRecord::create([
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'status' => 'draft',
            'currency' => 'MYR',
            'claim_expense_type' => 'receipt',
        ]);

        $this->actingAs($user)
            ->post('/records/'.$record->id.'/receipts', [
                'document_type' => 'waze_screenshot',
                'receipt' => UploadedFile::fake()->image('waze-route.jpg'),
            ])
            ->assertRedirect();

        $receipt = ExpenseReceipt::first();

        $this->assertSame('waze_screenshot', $receipt->document_type);
        $this->assertStringStartsWith('route-screenshots/', $receipt->file_path);
        Storage::disk('local')->assertExists($receipt->file_path);
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
        config(['expenseflow.notifications.finance_approval_email' => 'finance.hq@physiomobile.com']);
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
        config(['expenseflow.notifications.finance_approval_email' => 'finance.hq@physiomobile.com']);
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

    public function test_voided_records_are_removed_from_dashboard_operational_totals(): void
    {
        $this->withoutVite();
        $this->seed();

        $user = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $user->forceFill(['must_change_password' => false])->save();
        $category = ExpenseCategory::where('code', 'MEAL')->first();

        ExpenseRecord::create([
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'expense_category_id' => $category->id,
            'claim_reference_no' => 'PMEXP-202605-00004',
            'record_type' => ExpenseRecord::TYPE_CLAIMABLE,
            'merchant_name' => 'Active Merchant',
            'receipt_date' => '2026-05-10',
            'currency' => 'MYR',
            'total_amount' => '61.60',
            'description' => 'Active claim',
            'status' => 'pending_review',
        ]);
        ExpenseRecord::create([
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'claim_reference_no' => 'PMEXP-202605-00005',
            'record_type' => ExpenseRecord::TYPE_CLAIMABLE,
            'merchant_name' => 'Voided Merchant',
            'receipt_date' => '2026-05-10',
            'currency' => 'MYR',
            'total_amount' => '123.20',
            'description' => 'Wrong upload',
            'status' => 'voided',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Active Merchant');
        $response->assertSee('MYR 61.60');
        $response->assertSee('Meal');
        $response->assertDontSee('Voided Merchant');
        $response->assertDontSee('MYR 123.20');
        $response->assertDontSee('Uncategorised');
    }

    public function test_voided_records_are_hidden_from_records_index_until_filtered(): void
    {
        $this->withoutVite();
        $this->seed();

        $user = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $user->forceFill(['must_change_password' => false])->save();

        ExpenseRecord::create([
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'claim_reference_no' => 'PMEXP-202605-00006',
            'record_type' => ExpenseRecord::TYPE_CLAIMABLE,
            'merchant_name' => 'Hidden Voided Merchant',
            'receipt_date' => '2026-05-10',
            'currency' => 'MYR',
            'total_amount' => '77.00',
            'status' => 'voided',
        ]);

        $this->actingAs($user)
            ->get('/records')
            ->assertStatus(200)
            ->assertDontSee('Hidden Voided Merchant');

        $this->actingAs($user)
            ->get('/records?status=voided')
            ->assertStatus(200)
            ->assertSee('Hidden Voided Merchant');
    }

    public function test_voided_records_are_hidden_from_reports_until_filtered(): void
    {
        $this->withoutVite();
        $this->seed();

        $user = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $user->forceFill(['must_change_password' => false])->save();

        ExpenseRecord::create([
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'claim_reference_no' => 'PMEXP-202605-00007',
            'record_type' => ExpenseRecord::TYPE_CLAIMABLE,
            'merchant_name' => 'Report Voided Merchant',
            'receipt_date' => '2026-05-10',
            'currency' => 'MYR',
            'total_amount' => '88.00',
            'status' => 'voided',
        ]);

        $this->actingAs($user)
            ->get('/reports')
            ->assertStatus(200)
            ->assertDontSee('Report Voided Merchant');

        $this->actingAs($user)
            ->get('/reports?status=voided')
            ->assertStatus(200)
            ->assertSee('Report Voided Merchant');
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
