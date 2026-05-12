<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tcm_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tcm_admission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->dateTime('contact_at');
            $table->enum('contact_type', ['in_person', 'phone', 'video', 'email', 'collateral', 'home_visit'])->default('in_person');
            $table->integer('duration_minutes')->nullable();
            $table->string('cpt_code', 10)->default('T1017');
            $table->integer('units')->default(1);
            $table->string('place_of_service', 4)->default('12');
            $table->string('with_whom')->nullable(); // patient / family / provider / etc.
            $table->text('goals_addressed')->nullable();
            $table->text('summary')->nullable();
            $table->text('next_actions')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'contact_at']);
            $table->index(['tcm_admission_id', 'contact_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tcm_contacts');
    }
};
