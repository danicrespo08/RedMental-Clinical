<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('it_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('it_admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('therapist_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('session_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->string('cpt_code', 10)->default('90834');
            $table->string('modifier', 10)->nullable();
            $table->string('place_of_service', 4)->default('11');
            $table->integer('units')->default(1);

            // Inline progress note (SOAP-ish)
            $table->text('subjective')->nullable();
            $table->text('objective')->nullable();
            $table->text('assessment')->nullable();
            $table->text('plan')->nullable();
            $table->text('goals_addressed')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'session_date']);
            $table->index(['it_admission_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('it_sessions');
    }
};
