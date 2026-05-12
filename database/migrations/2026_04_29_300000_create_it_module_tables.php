<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IT (Individual Therapy) submodule tables.
 *
 * Mirrors the PSR module structure but adapted for individual therapy:
 *   - it_treatment_plans  + it_goals + it_objectives  (master treatment plan)
 *   - it_authorizations                                (insurance auth tracking)
 *   - it_service_log                                   (billable encounters)
 *   - it_superbill_week_locks                          (weekly lock for billing)
 *   - it_discharge_summaries                           (end-of-episode wrap)
 *
 * IT keeps `it_sessions` (created in 2026_04_21_210400) as the per-encounter
 * progress note, so no separate `it_progress_notes` table is created.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('it_treatment_plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('it_admission_id')->constrained('it_admissions')->cascadeOnDelete();
            $t->date('start_date');
            $t->date('end_date');
            $t->text('presenting_problem')->nullable();
            $t->text('long_term_goal')->nullable();
            $t->text('discharge_criteria')->nullable();
            $t->text('interventions')->nullable();
            $t->boolean('is_signed')->default(false);
            $t->timestamp('signed_at')->nullable();
            $t->foreignId('signed_by')->nullable()->constrained('employees')->nullOnDelete();
            $t->foreignId('signed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('it_goals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('it_treatment_plan_id')->constrained('it_treatment_plans')->cascadeOnDelete();
            $t->string('goal_code', 20);
            $t->text('description');
            $t->text('problem_statement')->nullable();
            $t->date('start_date');
            $t->date('target_date');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('it_objectives', function (Blueprint $t) {
            $t->id();
            $t->foreignId('it_goal_id')->constrained('it_goals')->cascadeOnDelete();
            $t->string('objective_code', 20);
            $t->text('description');
            $t->string('intervention_type', 100)->nullable();
            $t->text('intervention_description')->nullable();
            $t->date('start_date');
            $t->date('target_date');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('it_authorizations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('it_admission_id')->constrained('it_admissions')->cascadeOnDelete();
            $t->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('payer_id')->nullable()->constrained('payers')->nullOnDelete();

            $t->string('auth_number', 50);
            $t->string('auth_type', 30)->default('initial'); // initial, concurrent, retrospective
            $t->string('status', 30)->default('pending');    // pending, submitted, approved, denied, expired

            $t->date('requested_start_date')->nullable();
            $t->date('requested_end_date')->nullable();
            $t->date('approved_start_date')->nullable();
            $t->date('approved_end_date')->nullable();

            $t->unsignedInteger('approved_units')->default(0);
            $t->unsignedInteger('used_units')->default(0);

            $t->json('cpt_codes')->nullable(); // ["90834", "90837"]
            $t->text('denial_reason')->nullable();
            $t->text('notes')->nullable();

            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['client_id', 'status', 'approved_end_date']);
        });

        Schema::create('it_service_log', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('it_admission_id')->constrained('it_admissions')->cascadeOnDelete();
            $t->foreignId('it_session_id')->nullable()->constrained('it_sessions')->nullOnDelete();

            $t->date('service_date');
            $t->time('start_time')->nullable();
            $t->time('end_time')->nullable();
            $t->unsignedInteger('units')->default(0);
            $t->string('cpt_code', 20);
            $t->string('modifier', 20)->nullable();
            $t->string('place_of_service', 10)->nullable();
            $t->string('diagnosis_code', 20)->nullable();
            $t->string('diagnosis_description', 255)->nullable();

            $t->foreignId('therapist_id')->constrained('employees')->cascadeOnDelete();

            $t->foreignId('it_authorization_id')->nullable()->constrained('it_authorizations')->nullOnDelete();
            $t->string('auth_number', 50)->nullable();

            $t->enum('billing_status', ['unbilled', 'submitted', 'paid', 'denied', 'void'])->default('unbilled');
            $t->string('claim_number', 50)->nullable();
            $t->date('billed_date')->nullable();
            $t->date('paid_date')->nullable();
            $t->decimal('paid_amount', 10, 2)->nullable();
            $t->text('denial_reason')->nullable();

            $t->boolean('has_progress_note')->default(false);

            $t->text('notes')->nullable();
            $t->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $t->timestamps();
            $t->index(['client_id', 'billing_status', 'service_date']);
            $t->index(['patient_id', 'service_date']);
        });

        Schema::create('it_superbill_week_locks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->date('week_start_date');
            $t->foreignId('locked_by')->constrained('users')->cascadeOnDelete();
            $t->timestamp('locked_at');
            $t->string('supervisor_name')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->unique(['client_id', 'week_start_date'], 'it_superbill_lock_unique');
        });

        Schema::create('it_discharge_summaries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('it_admission_id')->constrained('it_admissions')->cascadeOnDelete();

            $t->date('discharge_date');
            $t->string('discharge_type', 30); // planned, administrative, ama, transfer, other
            $t->string('discharge_reason', 60)->nullable();

            $t->date('admission_date');
            $t->string('primary_dx_code', 20)->nullable();
            $t->string('primary_dx_description')->nullable();
            $t->string('dx_at_discharge_code', 20)->nullable();
            $t->string('dx_at_discharge_description')->nullable();

            $t->text('presenting_problems')->nullable();
            $t->text('treatment_summary')->nullable();
            $t->text('clinical_course')->nullable();
            $t->text('response_to_treatment')->nullable();
            $t->text('medications_at_discharge')->nullable();
            $t->text('risk_assessment_at_discharge')->nullable();

            $t->json('goals_outcome')->nullable();
            $t->integer('total_sessions_attended')->default(0);
            $t->integer('total_sessions_absent')->default(0);
            $t->integer('total_units_billed')->default(0);
            $t->integer('days_in_program')->default(0);

            $t->text('aftercare_plan')->nullable();
            $t->string('aftercare_level', 60)->nullable();
            $t->text('aftercare_referrals')->nullable();
            $t->text('follow_up_appointments')->nullable();
            $t->text('crisis_plan')->nullable();
            $t->text('patient_instructions')->nullable();

            $t->text('therapist_recommendation')->nullable();
            $t->string('prognosis', 30)->nullable(); // good, fair, guarded, poor

            $t->foreignId('therapist_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->boolean('is_signed')->default(false);
            $t->timestamp('signed_at')->nullable();
            $t->foreignId('signed_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $t->string('status', 20)->default('draft'); // draft, signed
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->unique('it_admission_id');
            $t->index(['client_id', 'discharge_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('it_discharge_summaries');
        Schema::dropIfExists('it_superbill_week_locks');
        Schema::dropIfExists('it_service_log');
        Schema::dropIfExists('it_authorizations');
        Schema::dropIfExists('it_objectives');
        Schema::dropIfExists('it_goals');
        Schema::dropIfExists('it_treatment_plans');
    }
};
