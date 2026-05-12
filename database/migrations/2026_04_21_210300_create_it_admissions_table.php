<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('it_admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('therapist_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('admission_date');
            $table->date('discharge_date')->nullable();
            $table->enum('status', ['admitted', 'on_hold', 'discharged'])->default('admitted');
            $table->string('diagnosis_code', 20)->nullable();
            $table->string('diagnosis_description')->nullable();
            $table->string('authorization_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['client_id', 'patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('it_admissions');
    }
};
