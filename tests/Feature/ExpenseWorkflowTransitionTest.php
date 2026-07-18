<?php

namespace Tests\Feature;

use App\Models\ExpenseRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseWorkflowTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_status_cannot_jump_claim_from_pending_review_to_paid(): void
    {
        $this->seed();

        $director = $this->director();
        $owner = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $record = ExpenseRecord::create([
            'user_id' => $owner->id,
            'record_type' => ExpenseRecord::TYPE_CLAIMABLE,
            'status' => 'pending_review',
            'receipt_date' => now()->toDateString(),
            'currency' => 'MYR',
            'total_amount' => 100,
        ]);

        $this->actingAs($director)
            ->patch(route('reports.bulk-status'), [
                'record_ids' => [$record->id],
                'status' => 'paid',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame('pending_review', $record->fresh()->status);
        $this->assertDatabaseMissing('expense_approvals', [
            'expense_record_id' => $record->id,
            'action' => 'paid',
        ]);
    }

    public function test_bulk_status_valid_approval_uses_transition_rows_and_timestamps(): void
    {
        $this->seed();

        $director = $this->director();
        $owner = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $record = ExpenseRecord::create([
            'user_id' => $owner->id,
            'record_type' => ExpenseRecord::TYPE_CLAIMABLE,
            'status' => 'pending_review',
            'receipt_date' => now()->toDateString(),
            'currency' => 'MYR',
            'total_amount' => 100,
        ]);

        $this->actingAs($director)
            ->patch(route('reports.bulk-status'), [
                'record_ids' => [$record->id],
                'status' => 'approved',
            ])
            ->assertRedirect();

        $record->refresh();
        $this->assertSame('approved', $record->status);
        $this->assertNotNull($record->approved_at);
        $this->assertDatabaseHas('expense_approvals', [
            'expense_record_id' => $record->id,
            'action' => 'approved',
            'previous_status' => 'pending_review',
            'new_status' => 'approved',
        ]);
    }

    public function test_clarification_response_uses_transition_audit_instead_of_direct_status_mutation(): void
    {
        $this->seed();

        $owner = User::factory()->create(['role' => 'staff', 'status' => 'active', 'must_change_password' => false]);
        $record = ExpenseRecord::create([
            'user_id' => $owner->id,
            'record_type' => ExpenseRecord::TYPE_CLAIMABLE,
            'status' => 'need_clarification',
            'receipt_date' => now()->toDateString(),
            'currency' => 'MYR',
            'total_amount' => 100,
        ]);

        $this->actingAs($owner)
            ->post(route('records.comments.store', $record), ['comment' => 'Added requested details.'])
            ->assertRedirect();

        $record->refresh();
        $this->assertSame('pending_review', $record->status);
        $this->assertNotNull($record->submitted_at);
        $this->assertDatabaseHas('expense_approvals', [
            'expense_record_id' => $record->id,
            'approver_id' => $owner->id,
            'action' => 'clarification_responded',
            'previous_status' => 'need_clarification',
            'new_status' => 'pending_review',
        ]);
    }

    private function director(): User
    {
        $director = User::where('role', 'director_super_admin')->firstOrFail();
        $director->forceFill(['must_change_password' => false])->save();

        return $director;
    }
}
