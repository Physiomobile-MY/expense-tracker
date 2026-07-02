<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_records', function (Blueprint $table) {
            $table->string('medical_patient_name', 255)->nullable()->after('hotel_num_children');
            $table->string('medical_relationship', 50)->nullable()->after('medical_patient_name');
            $table->string('medical_diagnosis', 255)->nullable()->after('medical_relationship');
            $table->string('medical_doctor_name', 255)->nullable()->after('medical_diagnosis');
            $table->decimal('medical_consultation_fee', 10, 2)->nullable()->after('medical_doctor_name');
            $table->decimal('medical_medication_fee', 10, 2)->nullable()->after('medical_consultation_fee');
            $table->boolean('medical_panel_clinic')->nullable()->after('medical_medication_fee');
        });
    }

    public function down(): void
    {
        Schema::table('expense_records', function (Blueprint $table) {
            $table->dropColumn([
                'medical_patient_name',
                'medical_relationship',
                'medical_diagnosis',
                'medical_doctor_name',
                'medical_consultation_fee',
                'medical_medication_fee',
                'medical_panel_clinic',
            ]);
        });
    }
};
