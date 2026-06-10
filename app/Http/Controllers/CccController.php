<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankImport;
use App\Models\BankTransaction;
use App\Models\CashflowDay;
use App\Models\CommunicationLog;
use App\Models\Creditor;
use App\Models\CreditorDebt;
use App\Models\FinancialTransaction;
use App\Models\PaymentPlan;
use App\Models\SoaEntry;
use App\Models\SystemSetting;
use App\Models\TransactionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class CccController extends Controller
{
    public function dashboard(Request $request): View
    {
        $this->authorizeView($request);

        $today = today();
        $cashflow = CashflowDay::whereDate('date', $today)->first();
        $settings = $this->financialSettings();
        $latestCashflow = CashflowDay::latest('date')->first();
        $cashAvailable = (float) ($cashflow?->closing_balance ?? $latestCashflow?->closing_balance ?? 0);
        $minimumReserve = (float) ($settings['minimum_cash_reserve'] ?? 0);
        $settlementBudget = max(0, $cashAvailable - $minimumReserve);

        $metrics = [
            'opening_balance' => (float) ($cashflow?->opening_balance ?? 0),
            'inflow_today' => (float) FinancialTransaction::whereDate('date', $today)->where('type', 'inflow')->sum('amount'),
            'outflow_today' => (float) FinancialTransaction::whereDate('date', $today)->where('type', 'outflow')->sum('amount'),
            'closing_balance' => $cashAvailable,
            'total_outstanding' => (float) Creditor::sum('current_outstanding'),
            'overdue_amount' => (float) CreditorDebt::whereDate('due_date', '<', $today)->where('outstanding_amount', '>', 0)->sum('outstanding_amount'),
            'planned_week' => (float) PaymentPlan::where('status', 'planned')->whereBetween('planned_payment_date', [$today, $today->copy()->endOfWeek()])->sum('planned_amount'),
            'planned_month' => (float) PaymentPlan::where('status', 'planned')->whereBetween('planned_payment_date', [$today, $today->copy()->endOfMonth()])->sum('planned_amount'),
            'cash_available' => $cashAvailable,
            'minimum_reserve' => $minimumReserve,
            'available_for_settlement' => $settlementBudget,
        ];

        $healthChecks = [
            'Bank CSV Uploaded' => BankImport::whereDate('statement_date', $today)->exists(),
            'Cashflow Updated' => CashflowDay::whereDate('date', $today)->exists(),
            'SOA Updated' => SoaEntry::whereDate('created_at', $today)->exists(),
            'Payment Plan Reviewed' => PaymentPlan::whereDate('updated_at', $today)->exists(),
            'Reconciliation Complete' => BankTransaction::whereDate('transaction_date', $today)->where('match_status', 'unmatched')->doesntExist(),
        ];
        $healthScore = (int) round((collect($healthChecks)->filter()->count() / max(1, count($healthChecks))) * 100);

        $recommendations = $this->settlementRecommendations($settlementBudget);

        $dailyTrend = CashflowDay::orderByDesc('date')->limit(14)->get()->sortBy('date');
        $monthlyFlow = FinancialTransaction::whereDate('date', '>=', today()->subMonths(6))
            ->orderBy('date')
            ->get()
            ->groupBy(fn (FinancialTransaction $transaction) => $transaction->date?->format('Y-m'))
            ->map(fn ($rows) => $rows->groupBy('type')->map(fn ($typed) => $typed->sum('amount')));

        $aging = [
            'Current' => CreditorDebt::whereDate('due_date', '>=', $today)->where('outstanding_amount', '>', 0)->sum('outstanding_amount'),
            '1-30' => CreditorDebt::whereBetween('due_date', [$today->copy()->subDays(30), $today->copy()->subDay()])->where('outstanding_amount', '>', 0)->sum('outstanding_amount'),
            '31-60' => CreditorDebt::whereBetween('due_date', [$today->copy()->subDays(60), $today->copy()->subDays(31)])->where('outstanding_amount', '>', 0)->sum('outstanding_amount'),
            '60+' => CreditorDebt::whereDate('due_date', '<', $today->copy()->subDays(60))->where('outstanding_amount', '>', 0)->sum('outstanding_amount'),
        ];

        $topCreditors = Creditor::where('current_outstanding', '>', 0)->orderByDesc('current_outstanding')->limit(10)->get();

        return view('ccc.dashboard', compact('metrics', 'healthChecks', 'healthScore', 'recommendations', 'dailyTrend', 'monthlyFlow', 'aging', 'topCreditors'));
    }

    public function cashflow(Request $request): View
    {
        $this->authorizeView($request);

        $items = CashflowDay::latest('date')->paginate(20);

        return view('ccc.cashflow', compact('items'));
    }

    public function storeCashflow(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'total_inflow' => ['required', 'numeric', 'min:0'],
            'total_outflow' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['closing_balance'] = round((float) $validated['opening_balance'] + (float) $validated['total_inflow'] - (float) $validated['total_outflow'], 2);
        $validated['created_by'] = $request->user()->id;

        CashflowDay::updateOrCreate(['date' => $validated['date']], $validated);

        return back()->with('status', 'Daily cashflow saved.');
    }

    public function transactions(Request $request): View
    {
        $this->authorizeView($request);

        $query = FinancialTransaction::with(['category', 'creditor', 'debt'])->latest('date')->latest('id');
        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }
        if ($request->filled('search')) {
            $search = "%{$request->search}%";
            $query->where(fn ($q) => $q->where('reference_number', 'like', $search)->orWhere('description', 'like', $search));
        }

        $items = $query->paginate(20)->withQueryString();
        $categories = TransactionCategory::where('status', 'active')->orderBy('type')->orderBy('name')->get();
        $creditors = Creditor::orderBy('creditor_name')->get();
        $debts = CreditorDebt::with('creditor')->where('outstanding_amount', '>', 0)->orderBy('due_date')->get();

        return view('ccc.transactions', compact('items', 'categories', 'creditors', 'debts'));
    }

    public function storeTransaction(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'type' => ['required', 'in:inflow,outflow'],
            'transaction_category_id' => ['nullable', 'exists:transaction_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'creditor_id' => ['nullable', 'exists:creditors,id'],
            'creditor_debt_id' => ['nullable', 'exists:creditor_debts,id'],
        ]);
        $validated['created_by'] = $request->user()->id;

        DB::transaction(function () use ($validated): void {
            $transaction = FinancialTransaction::create($validated);

            if ($transaction->type === 'outflow' && $transaction->creditor_id) {
                $this->applyCreditorPayment($transaction->creditor_id, $transaction->creditor_debt_id, (float) $transaction->amount, $transaction->date, $transaction->reference_number ?: 'TXN-'.$transaction->id, 'Payment recorded from transaction ledger', $transaction);
            }
        });

        return back()->with('status', 'Transaction recorded.');
    }

    public function creditors(Request $request): View
    {
        $this->authorizeView($request);

        $query = Creditor::query()->withCount('debts')->orderBy('priority')->orderBy('creditor_name');
        if ($request->filled('search')) {
            $search = "%{$request->search}%";
            $query->where(fn ($q) => $q->where('creditor_name', 'like', $search)->orWhere('company_name', 'like', $search));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $items = $query->paginate(20)->withQueryString();

        return view('ccc.creditors', compact('items'));
    }

    public function storeCreditor(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'creditor_name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:255'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'priority' => ['required', 'in:critical,high,normal,low'],
            'relationship_risk' => ['required', 'integer', 'min:1', 'max:5'],
            'status' => ['required', 'in:active,fully_paid,kiv,disputed'],
            'notes' => ['nullable', 'string'],
        ]);
        $validated['current_outstanding'] = $validated['opening_balance'];

        $creditor = Creditor::create($validated);
        if ((float) $creditor->opening_balance > 0) {
            $this->appendSoa($creditor, today(), 'OPENING', 'Opening balance', (float) $creditor->opening_balance, 0, 'creditor', $creditor->id);
        }

        return back()->with('status', 'Creditor created.');
    }

    public function updateCreditor(Request $request, Creditor $creditor): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'priority' => ['required', 'in:critical,high,normal,low'],
            'relationship_risk' => ['required', 'integer', 'min:1', 'max:5'],
            'status' => ['required', 'in:active,fully_paid,kiv,disputed'],
            'notes' => ['nullable', 'string'],
        ]);
        $creditor->update($validated);

        return back()->with('status', 'Creditor updated.');
    }

    public function debts(Request $request): View
    {
        $this->authorizeView($request);

        $query = CreditorDebt::with('creditor')->latest('due_date')->latest('id');
        if ($request->filled('creditor_id')) {
            $query->where('creditor_id', $request->creditor_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $items = $query->paginate(20)->withQueryString();
        $creditors = Creditor::orderBy('creditor_name')->get();

        return view('ccc.debts', compact('items', 'creditors'));
    }

    public function storeDebt(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'creditor_id' => ['required', 'exists:creditors,id'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'original_amount' => ['required', 'numeric', 'min:0.01'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:unpaid,partially_paid,paid,disputed'],
            'notes' => ['nullable', 'string'],
        ]);
        $validated['paid_amount'] = (float) ($validated['paid_amount'] ?? 0);
        $validated['outstanding_amount'] = max(0, (float) $validated['original_amount'] - (float) $validated['paid_amount']);

        DB::transaction(function () use ($validated): void {
            $debt = CreditorDebt::create($validated);
            $this->appendSoa($debt->creditor, $debt->invoice_date ?: today(), $debt->invoice_number ?: 'INV-'.$debt->id, 'Invoice added', (float) $debt->original_amount, 0, 'creditor_debt', $debt->id);
            if ((float) $debt->paid_amount > 0) {
                $this->appendSoa($debt->creditor, $debt->invoice_date ?: today(), ($debt->invoice_number ?: 'INV-'.$debt->id).'-PAID', 'Existing payment recorded with invoice', 0, (float) $debt->paid_amount, 'creditor_debt', $debt->id);
            }
            $this->recalculateCreditor($debt->creditor);
        });

        return back()->with('status', 'Debt record created.');
    }

    public function paymentPlans(Request $request): View
    {
        $this->authorizeView($request);

        $items = PaymentPlan::with(['creditor', 'debt'])->orderBy('planned_payment_date')->paginate(20);
        $creditors = Creditor::orderBy('creditor_name')->get();
        $debts = CreditorDebt::with('creditor')->where('outstanding_amount', '>', 0)->orderBy('due_date')->get();

        return view('ccc.payment-plans', compact('items', 'creditors', 'debts'));
    }

    public function storePaymentPlan(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'creditor_id' => ['required', 'exists:creditors,id'],
            'creditor_debt_id' => ['nullable', 'exists:creditor_debts,id'],
            'planned_payment_date' => ['required', 'date'],
            'planned_amount' => ['required', 'numeric', 'min:0.01'],
            'priority' => ['required', 'in:critical,high,normal,low'],
            'notes' => ['nullable', 'string'],
        ]);
        $validated['status'] = 'planned';

        PaymentPlan::create($validated);

        return back()->with('status', 'Payment plan created.');
    }

    public function markPaymentPlanPaid(Request $request, PaymentPlan $paymentPlan): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'actual_payment_date' => ['required', 'date'],
            'actual_amount_paid' => ['required', 'numeric', 'min:0.01'],
            'reference_number' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($paymentPlan, $validated, $request): void {
            $transaction = FinancialTransaction::create([
                'date' => $validated['actual_payment_date'],
                'type' => 'outflow',
                'transaction_category_id' => TransactionCategory::where('type', 'outflow')->where('name', 'Creditor Payment')->value('id'),
                'amount' => $validated['actual_amount_paid'],
                'payment_method' => 'Bank Transfer',
                'reference_number' => $validated['reference_number'] ?? 'PLAN-'.$paymentPlan->id,
                'description' => 'Payment plan marked as paid',
                'creditor_id' => $paymentPlan->creditor_id,
                'creditor_debt_id' => $paymentPlan->creditor_debt_id,
                'created_by' => $request->user()->id,
            ]);

            $paymentPlan->update([
                'status' => 'paid',
                'actual_payment_date' => $validated['actual_payment_date'],
                'actual_amount_paid' => $validated['actual_amount_paid'],
            ]);

            $this->applyCreditorPayment($paymentPlan->creditor_id, $paymentPlan->creditor_debt_id, (float) $validated['actual_amount_paid'], Carbon::parse($validated['actual_payment_date']), $transaction->reference_number, 'Payment plan settled', $transaction);
        });

        return back()->with('status', 'Payment plan marked as paid and balances updated.');
    }

    public function soa(Request $request): View|HttpResponse
    {
        $this->authorizeView($request);

        $creditors = Creditor::orderBy('creditor_name')->get();
        $selectedCreditor = $request->filled('creditor_id') ? Creditor::find($request->creditor_id) : $creditors->first();
        $entries = collect();
        if ($selectedCreditor) {
            $query = SoaEntry::where('creditor_id', $selectedCreditor->id)->orderBy('date')->orderBy('id');
            if ($request->filled('from')) {
                $query->whereDate('date', '>=', $request->from);
            }
            if ($request->filled('to')) {
                $query->whereDate('date', '<=', $request->to);
            }
            $entries = $query->get();
        }

        if ($request->get('export') === 'csv') {
            return $this->csv('soa.csv', $entries->map(fn ($entry) => [
                $entry->date?->format('Y-m-d'),
                $entry->reference,
                $entry->description,
                $entry->debit,
                $entry->credit,
                $entry->running_balance,
            ])->prepend(['Date', 'Reference', 'Description', 'Debit', 'Credit', 'Running Balance'])->all());
        }

        return view('ccc.soa', compact('creditors', 'selectedCreditor', 'entries'));
    }

    public function bankReconciliation(Request $request): View
    {
        $this->authorizeView($request);

        $accounts = BankAccount::orderBy('bank_name')->get();
        $imports = BankImport::with('account')->latest()->limit(10)->get();
        $transactions = BankTransaction::latest('transaction_date')->latest('id')->paginate(20);

        return view('ccc.bank-reconciliation', compact('accounts', 'imports', 'transactions'));
    }

    public function storeBankAccount(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
        ]);
        $validated['status'] = 'active';

        BankAccount::create($validated);

        return back()->with('status', 'Bank account created.');
    }

    public function uploadBankCsv(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'statement_date' => ['required', 'date'],
            'bank_provider' => ['required', 'string', 'max:255'],
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $path = $request->file('csv_file')->store('bank-imports');
        $import = BankImport::create([
            'bank_account_id' => $validated['bank_account_id'],
            'statement_date' => $validated['statement_date'],
            'bank_provider' => $validated['bank_provider'],
            'file_path' => $path,
            'uploaded_by' => $request->user()->id,
        ]);

        [$rows, $duplicates] = $this->parseBankCsv($request->file('csv_file')->getRealPath(), $import);
        $import->update(['rows_imported' => $rows, 'duplicates_skipped' => $duplicates]);

        return back()->with('status', "Bank CSV imported. {$rows} rows imported, {$duplicates} duplicates skipped.");
    }

    public function communicationLogs(Request $request): View
    {
        $this->authorizeView($request);

        $items = CommunicationLog::with('creditor')->latest('date')->paginate(20);
        $creditors = Creditor::orderBy('creditor_name')->get();

        return view('ccc.communication-logs', compact('items', 'creditors'));
    }

    public function storeCommunicationLog(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'creditor_id' => ['required', 'exists:creditors,id'],
            'date' => ['required', 'date'],
            'type' => ['required', 'in:call,whatsapp,email,meeting'],
            'outcome' => ['nullable', 'string'],
            'promise_date' => ['nullable', 'date'],
            'promise_amount' => ['nullable', 'numeric', 'min:0'],
            'next_follow_up_date' => ['nullable', 'date'],
        ]);
        $validated['created_by'] = $request->user()->id;

        CommunicationLog::create($validated);

        return back()->with('status', 'Communication log saved.');
    }

    public function reports(Request $request): View|HttpResponse
    {
        $this->authorizeView($request);

        $from = $request->date('from') ?: today()->startOfMonth();
        $to = $request->date('to') ?: today()->endOfMonth();

        $cashflow = CashflowDay::whereBetween('date', [$from, $to])->orderBy('date')->get();
        $categorySpending = FinancialTransaction::query()
            ->with('category')
            ->where('type', 'outflow')
            ->whereBetween('date', [$from, $to])
            ->selectRaw('transaction_category_id, sum(amount) as total')
            ->groupBy('transaction_category_id')
            ->orderByDesc('total')
            ->get();
        $plannedVsActual = PaymentPlan::whereBetween('planned_payment_date', [$from, $to])->get();
        $aging = CreditorDebt::with('creditor')->where('outstanding_amount', '>', 0)->orderBy('due_date')->get();

        if ($request->get('export') === 'csv') {
            return $this->csv('ccc-report.csv', $cashflow->map(fn ($row) => [
                $row->date?->format('Y-m-d'),
                $row->opening_balance,
                $row->total_inflow,
                $row->total_outflow,
                $row->closing_balance,
            ])->prepend(['Date', 'Opening', 'Inflow', 'Outflow', 'Closing'])->all());
        }

        return view('ccc.reports', compact('from', 'to', 'cashflow', 'categorySpending', 'plannedVsActual', 'aging'));
    }

    public function settings(Request $request): View
    {
        $this->authorizeView($request);

        $financial = SystemSetting::firstOrCreate(['key' => 'ccc_financial'], ['value' => $this->defaultFinancialSettings()]);
        $smtp = SystemSetting::firstOrCreate(['key' => 'ccc_smtp'], ['value' => $this->defaultSmtpSettings()]);

        return view('ccc.settings', compact('financial', 'smtp'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isDirector(), 403);

        $validated = $request->validate([
            'minimum_cash_reserve' => ['required', 'numeric', 'min:0'],
            'weekly_debt_budget' => ['required', 'numeric', 'min:0'],
            'monthly_debt_target' => ['required', 'numeric', 'min:0'],
            'overdue_threshold' => ['required', 'integer', 'min:0'],
            'critical_creditor_threshold' => ['required', 'numeric', 'min:0'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['nullable', 'string', 'max:50'],
            'sender_name' => ['nullable', 'string', 'max:255'],
            'sender_email' => ['nullable', 'email', 'max:255'],
        ]);

        SystemSetting::updateOrCreate(['key' => 'ccc_financial'], [
            'value' => collect($validated)->only(['minimum_cash_reserve', 'weekly_debt_budget', 'monthly_debt_target', 'overdue_threshold', 'critical_creditor_threshold'])->all(),
        ]);
        SystemSetting::updateOrCreate(['key' => 'ccc_smtp'], [
            'value' => collect($validated)->only(['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'sender_name', 'sender_email'])->all(),
        ]);

        return back()->with('status', 'CCC settings saved.');
    }

    private function authorizeView(Request $request): void
    {
        abort_unless($request->user()?->canViewCcc(), 403);
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()?->canManageCcc(), 403);
    }

    private function defaultFinancialSettings(): array
    {
        return [
            'minimum_cash_reserve' => 1500,
            'weekly_debt_budget' => 5000,
            'monthly_debt_target' => 20000,
            'overdue_threshold' => 30,
            'critical_creditor_threshold' => 10000,
        ];
    }

    private function defaultSmtpSettings(): array
    {
        return [
            'smtp_host' => null,
            'smtp_port' => 587,
            'smtp_username' => null,
            'smtp_password' => null,
            'smtp_encryption' => 'tls',
            'sender_name' => 'PMMY Group',
            'sender_email' => 'hq@physiomobile.com',
        ];
    }

    private function financialSettings(): array
    {
        return SystemSetting::firstOrCreate(['key' => 'ccc_financial'], ['value' => $this->defaultFinancialSettings()])->value;
    }

    private function settlementRecommendations(float $budget)
    {
        if ($budget <= 0) {
            return collect();
        }

        $priorityScores = ['critical' => 40, 'high' => 25, 'normal' => 10, 'low' => 0];
        $remaining = $budget;

        return CreditorDebt::with('creditor')
            ->where('outstanding_amount', '>', 0)
            ->get()
            ->sortByDesc(function (CreditorDebt $debt) use ($priorityScores): float {
                $overdueDays = $debt->due_date ? max(0, today()->diffInDays($debt->due_date, false) * -1) : 0;
                return $overdueDays + ($priorityScores[$debt->creditor?->priority] ?? 0) + ((int) $debt->creditor?->relationship_risk * 5) + min(25, ((float) $debt->outstanding_amount / 1000));
            })
            ->map(function (CreditorDebt $debt) use (&$remaining) {
                if ($remaining <= 0) {
                    return null;
                }

                $suggested = min((float) $debt->outstanding_amount, max(300, min($remaining, (float) $debt->outstanding_amount)));
                $remaining -= $suggested;

                return [
                    'creditor' => $debt->creditor,
                    'debt' => $debt,
                    'suggested_amount' => $suggested,
                    'expected_outstanding' => (float) $debt->outstanding_amount - $suggested,
                ];
            })
            ->filter()
            ->take(10)
            ->values();
    }

    private function applyCreditorPayment(int $creditorId, ?int $debtId, float $amount, Carbon|string $date, ?string $reference, string $description, FinancialTransaction $transaction): void
    {
        $creditor = Creditor::lockForUpdate()->findOrFail($creditorId);
        $remaining = $amount;
        $debts = CreditorDebt::where('creditor_id', $creditor->id)
            ->when($debtId, fn ($query) => $query->where('id', $debtId))
            ->where('outstanding_amount', '>', 0)
            ->orderBy('due_date')
            ->lockForUpdate()
            ->get();

        foreach ($debts as $debt) {
            if ($remaining <= 0) {
                break;
            }
            $applied = min($remaining, (float) $debt->outstanding_amount);
            $debt->paid_amount = (float) $debt->paid_amount + $applied;
            $debt->outstanding_amount = max(0, (float) $debt->outstanding_amount - $applied);
            $debt->status = $debt->outstanding_amount <= 0 ? 'paid' : 'partially_paid';
            $debt->save();
            $remaining -= $applied;
        }

        $this->appendSoa($creditor, $date, $reference ?: 'TXN-'.$transaction->id, $description, 0, $amount, 'transaction', $transaction->id);
        $this->recalculateCreditor($creditor);
    }

    private function recalculateCreditor(Creditor $creditor): void
    {
        $latestSoaBalance = $creditor->soaEntries()->latest('date')->latest('id')->value('running_balance');
        $creditor->current_outstanding = $latestSoaBalance !== null
            ? max(0, (float) $latestSoaBalance)
            : max(0, (float) $creditor->opening_balance + (float) $creditor->debts()->sum('outstanding_amount'));
        if ($creditor->current_outstanding <= 0 && $creditor->status !== 'disputed') {
            $creditor->status = 'fully_paid';
        } elseif ($creditor->status === 'fully_paid' && $creditor->current_outstanding > 0) {
            $creditor->status = 'active';
        }
        $creditor->save();
    }

    private function appendSoa(Creditor $creditor, Carbon|string $date, string $reference, string $description, float $debit, float $credit, string $sourceType, int $sourceId): void
    {
        $lastBalance = (float) SoaEntry::where('creditor_id', $creditor->id)->latest('date')->latest('id')->value('running_balance');
        SoaEntry::create([
            'creditor_id' => $creditor->id,
            'date' => $date,
            'reference' => $reference,
            'description' => $description,
            'debit' => $debit,
            'credit' => $credit,
            'running_balance' => $lastBalance + $debit - $credit,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);
    }

    private function parseBankCsv(string $path, BankImport $import): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return [0, 0];
        }

        $header = fgetcsv($handle) ?: [];
        $normalized = array_map(fn ($value) => str($value)->lower()->replace([' ', '_'], '')->toString(), $header);
        $rows = 0;
        $duplicates = 0;

        while (($line = fgetcsv($handle)) !== false) {
            $data = array_combine($normalized, array_pad($line, count($normalized), null));
            $date = $data['transactiondate'] ?? $data['date'] ?? $data['valuedate'] ?? null;
            $description = $data['description'] ?? $data['details'] ?? $data['transactiondescription'] ?? null;
            $debit = $this->money($data['debit'] ?? $data['withdrawal'] ?? $data['out'] ?? 0);
            $credit = $this->money($data['credit'] ?? $data['deposit'] ?? $data['in'] ?? 0);
            $balance = $this->money($data['balance'] ?? null);
            $reference = $data['reference'] ?? $data['ref'] ?? md5(($date ?? '').($description ?? '').$debit.$credit);

            if (! $date || (! $debit && ! $credit)) {
                continue;
            }

            $created = BankTransaction::firstOrCreate([
                'bank_account_id' => $import->bank_account_id,
                'transaction_date' => Carbon::parse($date)->format('Y-m-d'),
                'reference' => $reference,
                'debit' => $debit,
                'credit' => $credit,
            ], [
                'bank_import_id' => $import->id,
                'description' => $description,
                'balance' => $balance,
                'match_status' => $this->guessMatchStatus($description, $debit, $credit),
            ]);

            $created->wasRecentlyCreated ? $rows++ : $duplicates++;
        }

        fclose($handle);

        return [$rows, $duplicates];
    }

    private function guessMatchStatus(?string $description, float $debit, float $credit): string
    {
        $text = str($description ?? '')->lower()->toString();
        $matchedWords = ['salary', 'supplier', 'rental', 'rent', 'patient', 'panel', 'epf', 'socso', 'creditor'];

        return collect($matchedWords)->contains(fn ($word) => str_contains($text, $word)) ? 'matched' : 'unmatched';
    }

    private function money(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return round((float) str_replace([',', 'RM', ' '], '', (string) $value), 2);
    }

    private function csv(string $filename, array $rows): HttpResponse
    {
        $content = collect($rows)->map(fn ($row) => collect($row)->map(fn ($value) => '"'.str_replace('"', '""', (string) $value).'"')->implode(','))->implode("\n");

        return Response::make($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
