<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_records', function (Blueprint $table): void {
            $table->string('claim_expense_type')->nullable()->after('record_type')->index();
            $table->string('route_origin')->nullable()->after('project_cost_center');
            $table->string('route_destination')->nullable()->after('route_origin');
            $table->string('route_summary')->nullable()->after('route_destination');
            $table->decimal('route_distance_km', 8, 2)->nullable()->after('route_summary');
            $table->unsignedInteger('route_duration_minutes')->nullable()->after('route_distance_km');
            $table->string('route_arrival_time')->nullable()->after('route_duration_minutes');
            $table->decimal('mileage_rate', 8, 2)->nullable()->after('route_arrival_time');
            $table->decimal('mileage_amount', 12, 2)->nullable()->after('mileage_rate');
            $table->decimal('toll_amount', 12, 2)->nullable()->after('mileage_amount');
            $table->decimal('parking_amount', 12, 2)->nullable()->after('toll_amount');
        });
    }

    public function down(): void
    {
        Schema::table('expense_records', function (Blueprint $table): void {
            $table->dropIndex(['claim_expense_type']);
            $table->dropColumn([
                'claim_expense_type',
                'route_origin',
                'route_destination',
                'route_summary',
                'route_distance_km',
                'route_duration_minutes',
                'route_arrival_time',
                'mileage_rate',
                'mileage_amount',
                'toll_amount',
                'parking_amount',
            ]);
        });
    }
};
