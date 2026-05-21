<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_records', function (Blueprint $table): void {
            $table->json('toll_entries')->nullable()->after('toll_amount');
        });
    }

    public function down(): void
    {
        Schema::table('expense_records', function (Blueprint $table): void {
            $table->dropColumn('toll_entries');
        });
    }
};
