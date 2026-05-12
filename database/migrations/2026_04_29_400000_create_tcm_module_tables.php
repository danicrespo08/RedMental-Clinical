<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TCM (Targeted Case Management) submodule tables.
 *
 * Mirrors the PSR/IT module structure but adapted for case-management:
 *   - tcm_treatment_plans + tcm_goals + tcm_objectives  (service plan)
 *   - tcm_authorizations                                 (Medicaid pre-auth)
 *   - tcm_service_log                                    (billable encounters)
 *   - tcm_superbill_week_locks                           (weekly billing lock)
 *   - tcm_discharge_summaries                            (case closure)
 *
 * `tcm_contacts` (created in 2026_04_21_210600) remains the per-touch case-note
 * record; service_log holds the billable encounters fed into the superbill.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tcm_treatment_plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tcm_admission_id')->constrained('tcm_admissions')->cascadeOnDelete();
            $t->date('start_date');
            $t->date('end_date');
            $t->text('presenting_problem')->nullable();
            $t->text('long_term_goal')->nullable();
            $t->text('discharge_criteria')->nullable();
            $t->text('coordination_strategy')->nullable();
            $t->boolean('is_signed')->default(false);
            $t->timestamp('signed_at')->nullable();
            $t->foreignId('signed_by')->nullable()->constrained('employees')->nullOnDelete();
            $t->foreignId('signed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('tcm_goals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tcm_treatment_plan_id')->constrained('tcm_treatment_plans')->cascadeOnDelete();
            $t->string('goal_code', 20);
            $t->text('description');
            $t->text('problem_statement')->nullable();
            $t->date('start_date');
            $t->date('target_date');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('tcm_objectives', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tcm_goal_id')->constrained('tcm_goals')->cascadeOnDelete();
            $t->string('objective_code', 20);
            $t->text('description');
            $t->string('intervention_type', 100)->nullable();
            $t->text('intervention_description')->nullable();
            $t->date('start_date');
            $t->date('target_date');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('tcm_authorizations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('tcm_admission_id')->constrained('tcm_admissions')->cascadeOnDelete();
            $t->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('payer_id')->nullable()->constrained('payers')->nullOnDelete();

            $t->string('auth_number', 50);
            $t->string('auth_type', 30)->default('initial');
            $t->string('status', 30)->default('pending');

            $t->date('requested_start_date')->nullable();
            $t->date('requested_end_date')->nullable();
            $t->date('approved_start_date')->nullable();
            $t->date('approved_end_date')->nullable();

            $t->unsignedInteger('approved_units')->default(0);
            $t->unsignedInteger('used_units')->default(0);

            $t->json('cpt_codes')->nullable();
            $t->text('denial_reason')->nullable();
            $t->text('notes')->nullable();

            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['client_id', 'status', 'approved_end_date']);
        });

        Schema::create('tcm_service_log', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('tcm_admission_id')->constrained('tcm_admissions')->cascadeOnDelete();
            $t->foreignId('tcm_contact_id')->nullable()->constrained('tcm_contacts')->nullOnDelete();

            $t->date('service_date');
            $t->time('start_time')->nullable();
            $t->time('end_time')->nullable();
            $t->unsignedInteger('units')->default(0);
            $t->string('cpt_code', 20);
            $t->string('modifier', 20)->nullable();
            $t->string('place_of_service', 10)->nullable();
            $t->string('diagnosis_code', 20)->nullable();
            $t->string('diagnosis_description', 255)->nullable();

            $t->foreignId('case_manager_id')->constrained('employees')->cascadeOnDelete();

            $t->foreignId('tcm_authorization_id')->nullable()->constrained('tcm_authorizations')->nullOnDelete();
            $t->string('auth_number', 50)->nullable();

            $t->enum('billing_status', ['unbilled', 'submitted', 'paid', 'denied', 'void'])->default('unbilled');
            $t->string('claim_number', 50)->nullable();
            $t->date('billed_date')->nullable();
            $t->date('paid_date')->nullable();
            $t->decimal('paid_amount', 10, 2)->nullable();
            $t->text('denial_reason')->nullable();

            $t->boolean('has_contact_note')->default(false);

            $t->text('notes')->nullable();
            $t->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $t->timestamps();
            $t->index(['client_id', 'billing_status', 'service_date']);
            $t->index(['patient_id', 'service_date']);
        });

        Schema::create('tcm_superbill_week_locks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->date('week_start_date');
            $t->foreignId('locked_by')->constrained('users')->cascadeOnDelete();
            $t->timestamp('locked_at');
            $t->string('supervisor_name')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->unique(['client_id', 'week_start_date'], 'tcm_superbill_lock_unique');
        });

        Schema::create('tcm_discharge_summaries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('tcm_admission_id')->constrained('tcm_admissions')->cascadeOnDelete();

            $t->date('discharge_date');
            $t->string('discharge_type', 30);
            $t->string('discharge_reason', 60)->nullable();

            $t->date('admission_date');
            $t->string('primary_dx_code', 20)->nullable();
            $t->string('primary_dx_description')->nullable();
            $t->string('dx_at_discharge_code', 20)->nullable();
            $t->string('dx_at_discharge_description')->nullable();

            $t->text('presenting_problems')->nullable();
            $t->text('case_management_summary')->nullable();
            $t->text('coordination_outcomes')->nullable();
            $t->text('response_to_services')->nullable();
            $t->text('barriers_identified')->nullable();
            $t->text('risk_assessment_at_discharge')->nullable();

            $t->json('goals_outcome')->nullable();
            $t->integer('total_contacts')->default(0);
            $t->integer('total_units_billed')->default(0);
            $t->integer('days_in_program')->default(0);

            $t->text('aftercare_plan')->nullable();
            $t->string('aftercare_level', 60)->nullable();
            $t->text('aftercare_referrals')->nullable();
            $t->text('community_resources')->nullable();
            $t->text('crisis_plan')->nullable();
            $t->text('patient_instructions')->nullable();

            $t->text('case_manager_recommendation')->nullable();
            $t->string('prognosis', 30)->nullable();

            $t->foreignId('case_manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->boolean('is_signed')->default(false);
            $t->timestamp('signed_at')->nullable();
            $t->foreignId('signed_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $t->string('status', 20)->default('draft');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->unique('tcm_admission_id');
            $t->index(['client_id', 'discharge_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tcm_discharge_summaries');
        Schema::dropIfExists('tcm_superbill_week_locks');
        Schema::dropIfExists('tcm_service_log');
        Schema::dropIfExists('tcm_authorizations');
        Schema::dropIfExists('tcm_objectives');
        Schema::dropIfExists('tcm_goals');
        Schema::dropIfExists('tcm_treatment_plans');
    }
};
