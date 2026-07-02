<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_records', function (Blueprint $table) {
            $table->date('hotel_check_in_date')->nullable()->after('parking_amount');
            $table->date('hotel_check_out_date')->nullable()->after('hotel_check_in_date');
            $table->string('hotel_check_in_time', 10)->nullable()->after('hotel_check_out_date');
            $table->string('hotel_check_out_time', 10)->nullable()->after('hotel_check_in_time');
            $table->string('hotel_room_number', 50)->nullable()->after('hotel_check_out_time');
            $table->string('hotel_room_type', 100)->nullable()->after('hotel_room_number');
            $table->unsignedSmallInteger('hotel_num_nights')->nullable()->after('hotel_room_type');
            $table->unsignedSmallInteger('hotel_num_adults')->nullable()->after('hotel_num_nights');
            $table->unsignedSmallInteger('hotel_num_children')->nullable()->after('hotel_num_adults');
        });
    }

    public function down(): void
    {
        Schema::table('expense_records', function (Blueprint $table) {
            $table->dropColumn([
                'hotel_check_in_date',
                'hotel_check_out_date',
                'hotel_check_in_time',
                'hotel_check_out_time',
                'hotel_room_number',
                'hotel_room_type',
                'hotel_num_nights',
                'hotel_num_adults',
                'hotel_num_children',
            ]);
        });
    }
};
