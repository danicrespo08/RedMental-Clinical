<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TCM progress notes — case-management narrative notes tied to an admission,
 * independent of billable contacts. Supports sign-off and addendum.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tcm_progress_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('tcm_admission_id')->constrained('tcm_admissions')->cascadeOnDelete();
            $t->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();

            $t->date('note_date');
            $t->foreignId('case_manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->string('note_type', 40)->default('coordination'); // coordination, assessment, collateral, supervision, crisis, other

            $t->text('summary')->nullable();
            $t->text('interventions')->nullable();
            $t->text('coordination')->nullable();
            $t->text('progress')->nullable();
            $t->text('plan')->nullable();

            $t->string('risk_level', 20)->default('none'); // none, low, moderate, high
            $t->text('risk_notes')->nullable();
            $t->text('goals_addressed')->nullable();

            $t->string('status', 20)->default('draft'); // draft, signed, addendum
            $t->boolean('is_signed')->default(false);
            $t->timestamp('signed_at')->nullable();
            $t->foreignId('signed_by')->nullable()->constrained('employees')->nullOnDelete();
            $t->foreignId('signed_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $t->text('addendum_text')->nullable();
            $t->timestamp('addendum_date')->nullable();
            $t->foreignId('addendum_by')->nullable()->constrained('users')->nullOnDelete();

            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->index(['client_id', 'note_date']);
            $t->index(['tcm_admission_id', 'note_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tcm_progress_notes');
    }
};
