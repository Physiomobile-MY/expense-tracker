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

        $response = $this->actingAs(User::where('email', 'director@physiomobile.com')->first())
            ->get('/');

        $response->assertStatus(200);
    }

    public function test_staff_can_upload_receipt_for_manual_review_when_ai_is_not_configured(): void
    {
        $this->seed();
        Storage::fake('local');

        $response = $this->actingAs(User::where('email', 'staff@physiomobile.com')->first())
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

        $response = $this->actingAs(User::where('email', 'director@physiomobile.com')->first())
            ->get('/reports/export?format=xlsx');

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }
}
