<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->json('keywords')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('expense_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('claim_reference_no')->nullable()->unique();
            $table->string('record_type')->nullable()->index();
            $table->string('merchant_name')->nullable()->index();
            $table->text('merchant_address')->nullable();
            $table->date('receipt_date')->nullable()->index();
            $table->time('receipt_time')->nullable();
            $table->string('currency', 3)->default('MYR');
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->decimal('tax_amount', 12, 2)->nullable();
            $table->decimal('service_charge', 12, 2)->nullable();
            $table->decimal('discount', 12, 2)->nullable();
            $table->decimal('total_amount', 12, 2)->nullable()->index();
            $table->string('payment_method')->nullable();
            $table->string('receipt_number')->nullable()->index();
            $table->string('project_cost_center')->nullable();
            $table->text('description')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('draft')->index();
            $table->boolean('duplicate_warning')->default(false);
            $table->decimal('ai_confidence_score', 5, 4)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['record_type', 'status']);
            $table->index(['user_id', 'record_type', 'receipt_date']);
        });

        Schema::create('expense_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_record_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('file_type');
            $table->unsignedBigInteger('file_size');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('document_type')->default('receipt');
            $table->timestamps();
        });

        Schema::create('expense_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_record_id')->constrained()->cascadeOnDelete();
            $table->string('description')->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('ai_extraction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_record_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->default('openai');
            $table->string('model')->nullable();
            $table->longText('prompt')->nullable();
            $table->longText('raw_response')->nullable();
            $table->json('extracted_json')->nullable();
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('token_usage_input')->nullable();
            $table->unsignedInteger('token_usage_output')->nullable();
            $table->decimal('total_cost_estimate', 12, 6)->nullable();
            $table->timestamps();
        });

        Schema::create('expense_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->string('action')->index();
            $table->string('previous_status')->nullable();
            $table->string('new_status')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('expense_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('comment');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->string('module')->index();
            $table->unsignedBigInteger('record_id')->nullable()->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('expense_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('type')->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('expense_notifications');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('expense_comments');
        Schema::dropIfExists('expense_approvals');
        Schema::dropIfExists('ai_extraction_logs');
        Schema::dropIfExists('expense_receipt_items');
        Schema::dropIfExists('expense_receipts');
        Schema::dropIfExists('expense_records');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('departments');
    }
};
