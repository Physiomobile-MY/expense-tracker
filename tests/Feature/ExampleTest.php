<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
}
