<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Full PSR (Psychosocial Rehabilitation) schema with column-additions
 * consolidated.
 *
 * Tables created (in dependency order):
 *   psr_note_templates
 *   psr_admissions          (with referring provider, risk_score, default_shift_pos, diagnoses snapshot)
 *   psr_admission_documents
 *   psr_intakes             (consents, demographics, medical, safety, signature, form_data)
 *   psr_assessments_bio
 *   psr_diagnosis_history
 *   psr_fars                (Functional Assessment Rating Scale, 18 domains)
 *   psr_treatment_plans     (Master Treatment Plan)
 *   psr_goals
 *   psr_objectives
 *   psr_authorizations      (full insurance auth + DocuSign envelope fields)
 *   psr_eligibility_checks
 *   psr_group_sessions      (with break / activities)
 *   psr_group_session_attendees (with schedule_segments)
 *   psr_progress_notes      (SOAP/DAP/BIRP/GIRP, signing + co-sign + addendum, progress_rating, extra_data)
 *   psr_service_log         (with is_retroactive)
 *   psr_superbill_week_locks
 *   psr_discharge_summaries
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('psr_note_templates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->json('sections')->nullable();
            $t->boolean('is_system')->default(false);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('psr_admissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->foreignId('patient_id')->constrained()->cascadeOnDelete();

            $t->date('admission_date');
            $t->date('discharge_date')->nullable();
            $t->string('status', 30)->default('pending_intake'); // pending_intake, intake_complete, admitted, on_hold, discharged

            $t->foreignId('assigned_therapist_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->foreignId('referring_provider_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->date('referral_date')->nullable();

            // Diagnoses snapshot (also tracked historically in psr_diagnosis_history)
            $t->string('primary_dx_code', 20)->nullable();
            $t->string('primary_dx_description', 255)->nullable();
            $t->string('secondary_dx_code', 20)->nullable();
            $t->string('secondary_dx_description', 255)->nullable();

            // Default place of service / shift on admission for billing convenience
            $t->string('default_shift_pos', 10)->nullable();

            // Risk-score (computed from FARS + clinical signals)
            $t->unsignedSmallInteger('risk_score')->nullable();

            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();

            $t->index(['client_id', 'status'], 'psr_adm_client_status_idx');
            $t->index(['patient_id', 'status'], 'psr_adm_patient_status_idx');
        });

        Schema::create('psr_admission_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('psr_admission_id')->constrained()->cascadeOnDelete();
            $t->string('document_type');
            $t->string('original_name');
            $t->string('file_path');
            $t->string('mime_type')->nullable();
            $t->unsignedBigInteger('file_size')->default(0);
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('psr_intakes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('psr_admission_id')->constrained('psr_admissions')->cascadeOnDelete();

            $t->string('race', 60)->nullable();
            $t->string('ethnicity', 60)->nullable();
            $t->string('preferred_language', 60)->default('English');
            $t->boolean('interpreter_needed')->default(false);

            $t->text('legal_guardian_name')->nullable();
            $t->string('legal_guardian_relationship', 60)->nullable();
            $t->text('legal_guardian_phone')->nullable();

            $t->boolean('consent_treatment')->default(false);
            $t->boolean('consent_release_info')->default(false);
            $t->boolean('receipt_hipaa')->default(false);
            $t->boolean('receipt_rights')->default(false);
            $t->boolean('consent_telehealth')->default(false);
            $t->boolean('emergency_plan_ack')->default(false);

            $t->text('medical_history_checklist')->nullable();
            $t->text('allergies')->nullable();
            $t->text('current_medications')->nullable();

            $t->text('pcp_name')->nullable();
            $t->text('pcp_phone')->nullable();
            $t->text('psychiatrist_name')->nullable();
            $t->text('psychiatrist_phone')->nullable();

            $t->boolean('safety_contract_agreed')->default(false);
            $t->text('safety_plan_details')->nullable();

            $t->text('staff_comments')->nullable();

            // Free-form structured intake form data (flexible schema per clinic)
            $t->json('form_data')->nullable();

            $t->boolean('is_signed')->default(false);
            $t->timestamp('signed_at')->nullable();
            $t->longText('patient_signature_data')->nullable();
            $t->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();

            $t->timestamps();
        });

        Schema::create('psr_assessments_bio', function (Blueprint $t) {
            $t->id();
            $t->foreignId('psr_admission_id')->constrained('psr_admissions')->cascadeOnDelete();

            $t->text('presenting_problem')->nullable();
            $t->text('history_illness')->nullable();
            $t->text('family_history')->nullable();
            $t->text('medical_history')->nullable();
            $t->text('medications')->nullable();
            $t->text('risk_assessment')->nullable();
            $t->text('clinical_impression')->nullable();

            $t->boolean('is_signed')->default(false);
            $t->timestamp('signed_at')->nullable();
            $t->foreignId('signed_by')->nullable()->constrained('employees')->nullOnDelete();
            $t->foreignId('signed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('psr_diagnosis_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('psr_admission_id')->constrained('psr_admissions')->cascadeOnDelete();
            $t->date('effective_date');
            $t->string('primary_dx_code', 20);
            $t->string('primary_dx_description', 255)->nullable();
            $t->string('secondary_dx_code', 20)->nullable();
            $t->string('secondary_dx_description', 255)->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('psr_fars', function (Blueprint $t) {
            $t->id();
            $t->foreignId('psr_admission_id')->constrained('psr_admissions')->cascadeOnDelete();

            $t->string('evaluation_type', 30); // admission, periodic, discharge
            $t->dateTime('evaluation_date');

            // 18 FARS domains (1–9)
            $t->unsignedTinyInteger('depression')->default(1);
            $t->unsignedTinyInteger('security')->default(1);
            $t->unsignedTinyInteger('hyperaffect')->default(1);
            $t->unsignedTinyInteger('anxiety')->default(1);
            $t->unsignedTinyInteger('cognitive')->default(1);
            $t->unsignedTinyInteger('thought_process')->default(1);
            $t->unsignedTinyInteger('traumatic_stress')->default(1);
            $t->unsignedTinyInteger('medical_physical')->default(1);
            $t->unsignedTinyInteger('interpersonal')->default(1);
            $t->unsignedTinyInteger('family_relationships')->default(1);
            $t->unsignedTinyInteger('family_environment')->default(1);
            $t->unsignedTinyInteger('substance_use')->default(1);
            $t->unsignedTinyInteger('work_school')->default(1);
            $t->unsignedTinyInteger('socio_legal')->default(1);
            $t->unsignedTinyInteger('danger_others')->default(1);
            $t->unsignedTinyInteger('danger_self')->default(1);
            $t->unsignedTinyInteger('adl')->default(1);
            $t->unsignedTinyInteger('self_care')->default(1);

            $t->text('indicators_json')->nullable();
            $t->boolean('substance_abuse_history')->default(false);
            $t->unsignedSmallInteger('total_score')->default(18);
            $t->unsignedSmallInteger('mgaf_score')->nullable();

            $t->boolean('is_signed')->default(false);
            $t->timestamp('signed_at')->nullable();
            $t->foreignId('signed_by')->nullable()->constrained('employees')->nullOnDelete();
            $t->foreignId('signed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('psr_treatment_plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('psr_admission_id')->constrained('psr_admissions')->cascadeOnDelete();

            $t->date('start_date');
            $t->date('end_date');
            $t->text('strengths')->nullable();
            $t->text('weaknesses')->nullable();
            $t->text('services')->nullable();
            $t->text('strengths_other')->nullable();
            $t->text('weaknesses_other')->nullable();
            $t->text('long_term_goal')->nullable();
            $t->text('discharge_criteria')->nullable();

            $t->boolean('is_signed')->default(false);
            $t->timestamp('signed_at')->nullable();
            $t->foreignId('signed_by')->nullable()->constrained('employees')->nullOnDelete();
            $t->foreignId('signed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('psr_goals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('psr_treatment_plan_id')->constrained('psr_treatment_plans')->cascadeOnDelete();
            $t->string('goal_code', 20);
            $t->text('description');
            $t->text('problem_statement')->nullable();
            $t->date('start_date');
            $t->date('target_date');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('psr_objectives', function (Blueprint $t) {
            $t->id();
            $t->foreignId('psr_goal_id')->constrained('psr_goals')->cascadeOnDelete();
            $t->string('objective_code', 20);
            $t->text('description');
            $t->string('intervention_type', 100)->nullable();
            $t->text('intervention_description')->nullable();
            $t->date('start_date');
            $t->date('target_date');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('psr_authorizations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('psr_admission_id')->constrained('psr_admissions')->cascadeOnDelete();
            $t->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('payer_id')->nullable()->constrained('payers')->nullOnDelete();
            $t->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();

            $t->string('auth_number', 100)->nullable();
            $t->string('auth_type', 50)->default('initial');         // initial, concurrent, retroactive
            $t->string('status', 50)->default('pending');            // pending, submitted, approved, denied, expired, pending_review

            $t->string('service_code', 20)->nullable();              // e.g. H0035, H2017
            $t->string('service_description', 255)->nullable();
            $t->string('modifier_1', 10)->nullable();
            $t->string('modifier_2', 10)->nullable();
            $t->string('modifier_3', 10)->nullable();
            $t->string('modifier_4', 10)->nullable();
            $t->string('place_of_service', 10)->nullable();
            $t->string('revenue_code', 10)->nullable();

            $t->unsignedInteger('units_requested')->nullable();
            $t->unsignedInteger('units_approved')->nullable();
            $t->unsignedInteger('units_used')->default(0);
            $t->string('unit_type', 30)->default('units');           // units, hours, days, visits
            $t->string('frequency', 100)->nullable();                // e.g. 5 days/week, 4 hrs/day

            $t->date('requested_start_date')->nullable();
            $t->date('requested_end_date')->nullable();
            $t->date('approved_start_date')->nullable();
            $t->date('approved_end_date')->nullable();

            $t->foreignId('rendering_provider_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->foreignId('supervising_provider_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->string('group_npi', 20)->nullable();
            $t->string('rendering_npi', 20)->nullable();
            $t->string('taxonomy_code', 20)->nullable();

            $t->text('member_id')->nullable();
            $t->text('medicaid_id')->nullable();
            $t->string('payer_external_id', 50)->nullable();
            $t->string('plan_type', 50)->nullable();

            $t->string('primary_dx_code', 20)->nullable();
            $t->string('primary_dx_description', 255)->nullable();
            $t->string('secondary_dx_code', 20)->nullable();
            $t->string('secondary_dx_description', 255)->nullable();

            $t->text('clinical_justification')->nullable();
            $t->text('medical_necessity_statement')->nullable();

            $t->date('submission_date')->nullable();
            $t->date('decision_date')->nullable();
            $t->text('denial_reason')->nullable();
            $t->text('appeal_notes')->nullable();
            $t->string('contact_name', 150)->nullable();
            $t->string('contact_phone', 30)->nullable();
            $t->string('reference_number', 100)->nullable();

            $t->unsignedInteger('units_alert_threshold')->nullable(); // % to alert (e.g., 80)
            $t->unsignedSmallInteger('expiry_alert_days')->nullable();

            // DocuSign envelope tracking
            $t->string('docusign_envelope_id', 100)->nullable();
            $t->string('docusign_status', 30)->nullable();
            $t->timestamp('docusign_sent_at')->nullable();
            $t->timestamp('docusign_completed_at')->nullable();

            $t->text('notes')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();

            $t->index(['client_id', 'status']);
            $t->index(['psr_admission_id', 'status']);
            $t->index(['patient_id', 'status']);
            $t->index('auth_number');
            $t->index('approved_end_date');
        });

        Schema::create('psr_eligibility_checks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('psr_admission_id')->nullable()->constrained()->cascadeOnDelete();
            $t->foreignId('payer_id')->nullable()->constrained('payers')->nullOnDelete();

            $t->date('check_date');
            $t->string('member_id', 100)->nullable();
            $t->string('result', 30)->default('pending'); // active, terminated, pending, no_coverage, error
            $t->date('coverage_start')->nullable();
            $t->date('coverage_end')->nullable();
            $t->string('plan_name', 255)->nullable();
            $t->string('plan_type', 60)->nullable();
            $t->json('raw_response')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('psr_group_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();

            $t->date('session_date');
            $t->time('start_time');
            $t->time('end_time');

            $t->string('title', 255);
            $t->string('session_type', 50)->default('group_therapy');
            $t->string('service_code', 20)->default('H2017');
            $t->string('modifier', 20)->nullable();
            $t->string('place_of_service', 10)->default('11');

            $t->foreignId('lead_therapist_id')->constrained('employees')->restrictOnDelete();
            $t->foreignId('co_therapist_id')->nullable()->constrained('employees')->nullOnDelete();

            $t->unsignedSmallInteger('max_capacity')->default(10);

            // Break tracking (lunch / smoke break per FL requirements)
            $t->time('break_start_time')->nullable();
            $t->time('break_end_time')->nullable();
            $t->unsignedSmallInteger('break_minutes')->default(0);

            // Session activities (curriculum-style)
            $t->json('activities')->nullable();

            $t->text('session_summary')->nullable();
            $t->text('notes')->nullable();

            $t->string('status', 30)->default('scheduled'); // scheduled, in_progress, completed, cancelled

            $t->boolean('is_signed')->default(false);
            $t->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('signed_at')->nullable();

            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->softDeletes();

            $t->index(['client_id', 'clinic_id', 'session_date']);
            $t->index(['session_date', 'status']);
            $t->index('lead_therapist_id');
        });

        Schema::create('psr_group_session_attendees', function (Blueprint $t) {
            $t->id();
            $t->foreignId('psr_group_session_id')->constrained('psr_group_sessions')->cascadeOnDelete();
            $t->foreignId('psr_admission_id')->constrained('psr_admissions')->cascadeOnDelete();
            $t->foreignId('patient_id')->constrained()->cascadeOnDelete();

            $t->string('attendance_status', 30)->default('present'); // present, absent, late, left_early
            $t->time('check_in_time')->nullable();
            $t->time('check_out_time')->nullable();

            // Per-attendee schedule segments (e.g. partial attendance, multiple windows)
            $t->json('schedule_segments')->nullable();

            $t->unsignedSmallInteger('units')->default(4);
            $t->string('participation_level', 30)->nullable();

            $t->text('individual_notes')->nullable();

            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();

            $t->unique(['psr_group_session_id', 'psr_admission_id'], 'gs_admission_unique');
            $t->index('attendance_status');
        });

        Schema::create('psr_progress_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $t->foreignId('psr_group_session_id')->nullable()->constrained('psr_group_sessions')->nullOnDelete();
            $t->foreignId('psr_admission_id')->constrained('psr_admissions')->cascadeOnDelete();
            $t->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $t->foreignId('note_template_id')->nullable()->constrained('psr_note_templates')->nullOnDelete();

            $t->date('note_date');
            $t->time('start_time')->nullable();
            $t->time('end_time')->nullable();
            $t->unsignedInteger('units')->default(0);
            $t->string('service_code', 20)->nullable();
            $t->string('modifier', 20)->nullable();
            $t->string('place_of_service', 10)->nullable();

            $t->foreignId('therapist_id')->constrained('employees')->cascadeOnDelete();

            // Clinical content (PHI)
            $t->text('subjective')->nullable();
            $t->text('objective')->nullable();
            $t->text('intervention')->nullable();
            $t->text('response')->nullable();
            $t->text('progress')->nullable();
            $t->text('plan')->nullable();

            $t->string('mood', 50)->nullable();
            $t->string('affect', 50)->nullable();
            $t->enum('risk_level', ['none', 'low', 'moderate', 'high'])->default('none');
            $t->text('risk_notes')->nullable();

            // [{goal_id, objective_id, status, note}]
            $t->json('goals_addressed')->nullable();

            $t->string('participation_level', 30)->nullable();
            $t->string('session_type', 50)->nullable();

            // Numeric progress rating per session (1–5 scale, optional)
            $t->unsignedTinyInteger('progress_rating')->nullable();

            // Template-specific extra data (mental_status, etc. — schema flexes per template)
            $t->json('extra_data')->nullable();

            $t->enum('status', ['draft', 'signed', 'addendum'])->default('draft');
            $t->boolean('is_signed')->default(false);
            $t->timestamp('signed_at')->nullable();
            $t->foreignId('signed_by')->nullable()->constrained('employees')->nullOnDelete();
            $t->foreignId('signed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('co_signer_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->timestamp('co_signed_at')->nullable();

            $t->text('addendum_text')->nullable();
            $t->timestamp('addendum_date')->nullable();
            $t->foreignId('addendum_by')->nullable()->constrained('users')->nullOnDelete();

            $t->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $t->timestamps();

            $t->unique(['psr_group_session_id', 'patient_id'], 'unique_note_per_patient_session');
            $t->index(['client_id', 'note_date']);
            $t->index(['patient_id', 'note_date']);
        });

        Schema::create('psr_service_log', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $t->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $t->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $t->foreignId('psr_admission_id')->constrained('psr_admissions')->cascadeOnDelete();

            $t->date('service_date');
            $t->time('start_time')->nullable();
            $t->time('end_time')->nullable();
            $t->unsignedInteger('units')->default(0);
            $t->string('service_code', 20);
            $t->string('modifier', 20)->nullable();
            $t->string('place_of_service', 10)->nullable();
            $t->string('diagnosis_code', 20)->nullable();
            $t->string('diagnosis_description', 255)->nullable();

            $t->foreignId('therapist_id')->constrained('employees')->cascadeOnDelete();

            $t->enum('source_type', ['group_session', 'individual', 'assessment', 'retroactive'])->default('group_session');
            $t->foreignId('psr_group_session_id')->nullable()->constrained('psr_group_sessions')->nullOnDelete();
            $t->unsignedBigInteger('psr_group_session_attendee_id')->nullable();
            $t->foreignId('psr_progress_note_id')->nullable()->constrained('psr_progress_notes')->nullOnDelete();

            $t->foreignId('psr_authorization_id')->nullable()->constrained('psr_authorizations')->nullOnDelete();
            $t->string('auth_number', 50)->nullable();

            $t->enum('billing_status', ['unbilled', 'submitted', 'paid', 'denied', 'void'])->default('unbilled');
            $t->string('claim_number', 50)->nullable();
            $t->date('billed_date')->nullable();
            $t->date('paid_date')->nullable();
            $t->decimal('paid_amount', 10, 2)->nullable();
            $t->text('denial_reason')->nullable();

            $t->boolean('has_progress_note')->default(false);
            $t->string('note_status', 20)->nullable();

            // Retroactive entries marked separately (auth-after-the-fact workflow)
            $t->boolean('is_retroactive')->default(false);

            $t->text('notes')->nullable();
            $t->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $t->timestamps();

            $t->unique('psr_group_session_attendee_id', 'unique_log_per_attendee');
            $t->index(['client_id', 'billing_status', 'service_date']);
            $t->index(['patient_id', 'service_date']);
        });

        Schema::create('psr_superbill_week_locks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $t->date('week_start_date');
            $t->foreignId('locked_by')->constrained('users')->cascadeOnDelete();
            $t->timestamp('locked_at');
            $t->string('supervisor_name')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();

            $t->unique(['client_id', 'clinic_id', 'week_start_date'], 'superbill_lock_unique');
        });

        Schema::create('psr_discharge_summaries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $t->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('psr_admission_id')->constrained('psr_admissions')->cascadeOnDelete();

            $t->date('discharge_date');
            $t->string('discharge_type', 30); // planned, administrative, ama, transfer, other
            $t->string('discharge_reason', 60)->nullable();

            $t->date('admission_date');
            $t->string('primary_dx_code', 20)->nullable();
            $t->string('primary_dx_description')->nullable();
            $t->string('secondary_dx_code', 20)->nullable();
            $t->string('secondary_dx_description')->nullable();
            $t->string('dx_at_discharge_code', 20)->nullable();
            $t->string('dx_at_discharge_description')->nullable();

            $t->text('presenting_problems')->nullable();
            $t->text('treatment_summary')->nullable();
            $t->text('clinical_course')->nullable();
            $t->text('response_to_treatment')->nullable();
            $t->text('medications_at_discharge')->nullable();
            $t->text('risk_assessment_at_discharge')->nullable();

            $t->json('goals_outcome')->nullable();
            $t->json('fars_comparison')->nullable();
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
            $t->foreignId('co_signer_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->timestamp('co_signed_at')->nullable();

            $t->string('status', 20)->default('draft'); // draft, signed, co_signed
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->unique('psr_admission_id');
            $t->index(['client_id', 'discharge_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psr_discharge_summaries');
        Schema::dropIfExists('psr_superbill_week_locks');
        Schema::dropIfExists('psr_service_log');
        Schema::dropIfExists('psr_progress_notes');
        Schema::dropIfExists('psr_group_session_attendees');
        Schema::dropIfExists('psr_group_sessions');
        Schema::dropIfExists('psr_eligibility_checks');
        Schema::dropIfExists('psr_authorizations');
        Schema::dropIfExists('psr_objectives');
        Schema::dropIfExists('psr_goals');
        Schema::dropIfExists('psr_treatment_plans');
        Schema::dropIfExists('psr_fars');
        Schema::dropIfExists('psr_diagnosis_history');
        Schema::dropIfExists('psr_assessments_bio');
        Schema::dropIfExists('psr_intakes');
        Schema::dropIfExists('psr_admission_documents');
        Schema::dropIfExists('psr_admissions');
        Schema::dropIfExists('psr_note_templates');
    }
};
