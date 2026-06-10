<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->index();
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->unique(['name', 'type']);
        });

        Schema::create('cashflow_days', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('total_inflow', 14, 2)->default(0);
            $table->decimal('total_outflow', 14, 2)->default(0);
            $table->decimal('closing_balance', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('creditors', function (Blueprint $table) {
            $table->id();
            $table->string('creditor_name')->index();
            $table->string('company_name')->nullable()->index();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('current_outstanding', 14, 2)->default(0)->index();
            $table->string('priority')->default('normal')->index();
            $table->unsignedTinyInteger('relationship_risk')->default(3);
            $table->string('status')->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('creditor_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creditor_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->nullable()->index();
            $table->date('invoice_date')->nullable()->index();
            $table->date('due_date')->nullable()->index();
            $table->decimal('original_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('outstanding_amount', 14, 2)->default(0)->index();
            $table->string('status')->default('unpaid')->index();
            $table->string('attachment_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('type')->index();
            $table->foreignId('transaction_category_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('payment_method')->nullable();
            $table->string('reference_number')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('creditor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('creditor_debt_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creditor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creditor_debt_id')->nullable()->constrained()->nullOnDelete();
            $table->date('planned_payment_date')->index();
            $table->decimal('planned_amount', 14, 2);
            $table->string('priority')->default('normal')->index();
            $table->string('status')->default('planned')->index();
            $table->date('actual_payment_date')->nullable()->index();
            $table->decimal('actual_amount_paid', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('soa_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creditor_id')->constrained()->cascadeOnDelete();
            $table->date('date')->index();
            $table->string('reference')->nullable()->index();
            $table->text('description')->nullable();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->decimal('running_balance', 14, 2)->default(0);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number')->nullable();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('bank_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->date('statement_date')->index();
            $table->string('bank_provider')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedInteger('rows_imported')->default(0);
            $table->unsignedInteger('duplicates_skipped')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_import_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->date('transaction_date')->index();
            $table->text('description')->nullable();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->nullable();
            $table->string('reference')->nullable()->index();
            $table->string('match_status')->default('unmatched')->index();
            $table->timestamps();

            $table->unique(['bank_account_id', 'transaction_date', 'reference', 'debit', 'credit'], 'bank_txn_unique_guard');
        });

        Schema::create('reconciliation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('status')->default('unmatched')->index();
            $table->decimal('matched_amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creditor_id')->constrained()->cascadeOnDelete();
            $table->date('date')->index();
            $table->string('type')->index();
            $table->text('outcome')->nullable();
            $table->date('promise_date')->nullable()->index();
            $table->decimal('promise_amount', 14, 2)->nullable();
            $table->date('next_follow_up_date')->nullable()->index();
            $table->string('attachment_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('attachable_type');
            $table->unsignedBigInteger('attachable_id');
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id']);
        });

        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient');
            $table->string('subject');
            $table->string('status')->default('pending')->index();
            $table->text('body')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('communication_logs');
        Schema::dropIfExists('reconciliation_records');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_imports');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('soa_entries');
        Schema::dropIfExists('payment_plans');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('creditor_debts');
        Schema::dropIfExists('creditors');
        Schema::dropIfExists('cashflow_days');
        Schema::dropIfExists('transaction_categories');
    }
};
