<?php

namespace Tests\Feature;

use App\Models\Creditor;
use App\Models\CreditorDebt;
use App\Models\PaymentPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashflowCommandCentreTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_view_ccc_dashboard(): void
    {
        $this->withoutVite();
        $this->seed();

        $director = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $director->forceFill(['must_change_password' => false])->save();

        $this->actingAs($director)
            ->get('/ccc')
            ->assertOk()
            ->assertSee('Cashflow Command Centre')
            ->assertSee('Finance Health Score')
            ->assertSee('Smart Settlement Recommendation');
    }

    public function test_staff_cannot_view_ccc(): void
    {
        $this->seed();

        $staff = User::factory()->create([
            'role' => 'staff',
            'status' => 'active',
            'must_change_password' => false,
        ]);
        $staff->syncRoles(['staff']);

        $this->actingAs($staff)
            ->get('/ccc')
            ->assertForbidden();
    }

    public function test_payment_plan_paid_updates_transaction_debt_creditor_and_soa(): void
    {
        $this->withoutVite();
        $this->seed();

        $director = User::where('email', 'nidzamyatimi@physiomobile.com')->first();
        $director->forceFill(['must_change_password' => false])->save();

        $this->actingAs($director)
            ->post('/ccc/creditors', [
                'creditor_name' => 'Supplier A',
                'company_name' => 'Supplier A Sdn Bhd',
                'opening_balance' => 0,
                'priority' => 'critical',
                'relationship_risk' => 5,
                'status' => 'active',
            ])
            ->assertRedirect();

        $creditor = Creditor::where('creditor_name', 'Supplier A')->firstOrFail();

        $this->actingAs($director)
            ->post('/ccc/debts', [
                'creditor_id' => $creditor->id,
                'invoice_number' => 'INV-001',
                'invoice_date' => '2026-06-01',
                'due_date' => '2026-06-05',
                'original_amount' => 1000,
                'paid_amount' => 0,
                'status' => 'unpaid',
            ])
            ->assertRedirect();

        $debt = CreditorDebt::where('invoice_number', 'INV-001')->firstOrFail();

        $this->actingAs($director)
            ->post('/ccc/payment-plans', [
                'creditor_id' => $creditor->id,
                'creditor_debt_id' => $debt->id,
                'planned_payment_date' => '2026-06-10',
                'planned_amount' => 400,
                'priority' => 'critical',
            ])
            ->assertRedirect();

        $plan = PaymentPlan::firstOrFail();

        $this->actingAs($director)
            ->post("/ccc/payment-plans/{$plan->id}/paid", [
                'actual_payment_date' => '2026-06-09',
                'actual_amount_paid' => 400,
                'reference_number' => 'PAY-001',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payment_plans', [
            'id' => $plan->id,
            'status' => 'paid',
            'actual_amount_paid' => 400,
        ]);
        $this->assertDatabaseHas('creditor_debts', [
            'id' => $debt->id,
            'paid_amount' => 400,
            'outstanding_amount' => 600,
            'status' => 'partially_paid',
        ]);
        $this->assertDatabaseHas('creditors', [
            'id' => $creditor->id,
            'current_outstanding' => 600,
        ]);
        $this->assertDatabaseHas('transactions', [
            'reference_number' => 'PAY-001',
            'type' => 'outflow',
            'amount' => 400,
        ]);
        $this->assertDatabaseHas('soa_entries', [
            'reference' => 'PAY-001',
            'credit' => 400,
            'running_balance' => 600,
        ]);
    }
}
