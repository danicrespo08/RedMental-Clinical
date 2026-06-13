<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Hhrr\ClinicController;
use App\Http\Controllers\Hhrr\DepartmentController;
use App\Http\Controllers\Hhrr\EmployeeController;
use App\Http\Controllers\Hhrr\PatientController;
use App\Http\Controllers\Hhrr\PayerController;
use App\Http\Controllers\Clinical\It\AdmissionController as ItAdmissionController;
use App\Http\Controllers\Clinical\It\AuthorizationController as ItAuthorizationController;
use App\Http\Controllers\Clinical\It\DischargeController as ItDischargeController;
use App\Http\Controllers\Clinical\It\ServiceLogController as ItServiceLogController;
use App\Http\Controllers\Clinical\It\SessionController as ItSessionController;
use App\Http\Controllers\Clinical\It\SuperbillController as ItSuperbillController;
use App\Http\Controllers\Clinical\It\TreatmentPlanController as ItTreatmentPlanController;
use App\Http\Controllers\Clinical\Psr\PsrAdmissionController;
use App\Http\Controllers\Clinical\Psr\PsrAssessmentController;
use App\Http\Controllers\Clinical\Psr\PsrAuthorizationController;
use App\Http\Controllers\Clinical\Psr\PsrIntakeController;
use App\Http\Controllers\Clinical\Psr\PsrDischargeController;
use App\Http\Controllers\Clinical\Psr\PsrGroupSessionController;
use App\Http\Controllers\Clinical\Psr\PsrProgressNoteController;
use App\Http\Controllers\Clinical\Psr\PsrServiceLogController;
use App\Http\Controllers\Clinical\Psr\PsrSuperbillController;
use App\Http\Controllers\Clinical\Psr\PsrTreatmentPlanController;
use App\Http\Controllers\Clinical\Tcm\AdmissionController as TcmAdmissionController;
use App\Http\Controllers\Clinical\Tcm\AuthorizationController as TcmAuthorizationController;
use App\Http\Controllers\Clinical\Tcm\ContactController as TcmContactController;
use App\Http\Controllers\Clinical\Tcm\ProgressNoteController as TcmProgressNoteController;
use App\Http\Controllers\Clinical\Tcm\DischargeController as TcmDischargeController;
use App\Http\Controllers\Clinical\Tcm\ServiceLogController as TcmServiceLogController;
use App\Http\Controllers\Clinical\Tcm\SuperbillController as TcmSuperbillController;
use App\Http\Controllers\Clinical\Tcm\TreatmentPlanController as TcmTreatmentPlanController;
use App\Http\Controllers\SuperAdmin\ClientController as SuperAdminClientController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// Guest routes (not authenticated)
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('super_admin')->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::resource('clients', SuperAdminClientController::class);
    });

    Route::middleware('client_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('roles/matrix',  [AdminRoleController::class, 'matrix'])->name('roles.matrix');
        Route::post('roles/matrix', [AdminRoleController::class, 'saveMatrix'])->name('roles.matrix.save');
        Route::resource('roles', AdminRoleController::class);
        Route::resource('users', AdminUserController::class);
    });

    // Audit log (Super Admin sees everything; Client Admin sees their tenant)
    Route::middleware('permission:system.audit.view')->group(function () {
        Route::get('admin/audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('admin.audit.index');
    });

    // Each resource is gated by granular permissions (view / create / edit / delete).
    Route::prefix('hhrr')->name('hhrr.')->group(function () {

        // Clinics
        Route::middleware('permission:hhrr.clinics.view')->group(function () {
            Route::get('clinics', [ClinicController::class, 'index'])->name('clinics.index');
        });
        Route::middleware('permission:hhrr.clinics.create')->group(function () {
            Route::get('clinics/create', [ClinicController::class, 'create'])->name('clinics.create');
            Route::post('clinics',       [ClinicController::class, 'store'])->name('clinics.store');
        });
        Route::middleware('permission:hhrr.clinics.view')->group(function () {
            Route::get('clinics/{clinic}', [ClinicController::class, 'show'])->name('clinics.show');
        });
        Route::middleware('permission:hhrr.clinics.edit')->group(function () {
            Route::get('clinics/{clinic}/edit', [ClinicController::class, 'edit'])->name('clinics.edit');
            Route::put('clinics/{clinic}',      [ClinicController::class, 'update'])->name('clinics.update');
        });
        Route::middleware('permission:hhrr.clinics.delete')->group(function () {
            Route::delete('clinics/{clinic}', [ClinicController::class, 'destroy'])->name('clinics.destroy');
        });

        // Departments
        Route::middleware('permission:hhrr.departments.view')->group(function () {
            Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
        });
        Route::middleware('permission:hhrr.departments.create')->group(function () {
            Route::get('departments/create',  [DepartmentController::class, 'create'])->name('departments.create');
            Route::post('departments',        [DepartmentController::class, 'store'])->name('departments.store');
        });
        Route::middleware('permission:hhrr.departments.edit')->group(function () {
            Route::get('departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
            Route::put('departments/{department}',      [DepartmentController::class, 'update'])->name('departments.update');
        });
        Route::middleware('permission:hhrr.departments.delete')->group(function () {
            Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
        });

        // Payers
        Route::middleware('permission:hhrr.payers.view')->group(function () {
            Route::get('payers', [PayerController::class, 'index'])->name('payers.index');
        });
        Route::middleware('permission:hhrr.payers.create')->group(function () {
            Route::get('payers/create', [PayerController::class, 'create'])->name('payers.create');
            Route::post('payers',       [PayerController::class, 'store'])->name('payers.store');
        });
        Route::middleware('permission:hhrr.payers.edit')->group(function () {
            Route::get('payers/{payer}/edit', [PayerController::class, 'edit'])->name('payers.edit');
            Route::put('payers/{payer}',      [PayerController::class, 'update'])->name('payers.update');
        });
        Route::middleware('permission:hhrr.payers.delete')->group(function () {
            Route::delete('payers/{payer}', [PayerController::class, 'destroy'])->name('payers.destroy');
        });

        // Employees — literal /create must register before /{employee} so the
        // wildcard binding doesn't try to find a model with id "create".
        Route::middleware('permission:hhrr.employees.view')->group(function () {
            Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
        });
        Route::middleware('permission:hhrr.employees.create')->group(function () {
            Route::get('employees/create', [EmployeeController::class, 'create'])->name('employees.create');
            Route::post('employees',       [EmployeeController::class, 'store'])->name('employees.store');
        });
        Route::middleware('permission:hhrr.employees.view')->group(function () {
            Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        });
        Route::middleware('permission:hhrr.employees.edit')->group(function () {
            Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
            Route::put('employees/{employee}',      [EmployeeController::class, 'update'])->name('employees.update');
        });
        Route::middleware('permission:hhrr.employees.delete')->group(function () {
            Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
        });

        // Patients — same pattern: /create must come before /{patient}.
        Route::middleware('permission:hhrr.patients.create')->group(function () {
            Route::get('patients/create', [PatientController::class, 'create'])->name('patients.create');
            Route::post('patients',       [PatientController::class, 'store'])->name('patients.store');
        });
        Route::middleware('permission:hhrr.patients.view')->group(function () {
            Route::get('patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
        });
        Route::middleware('permission:hhrr.patients.edit')->group(function () {
            Route::get('patients/{patient}/edit', [PatientController::class, 'edit'])->name('patients.edit');
            Route::put('patients/{patient}',      [PatientController::class, 'update'])->name('patients.update');
        });
        Route::middleware('permission:hhrr.patients.delete')->group(function () {
            Route::delete('patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');
        });
    });

    Route::prefix('clinical')->name('clinical.')->group(function () {

        // Literal /create/index URIs must register before /{model} bindings.
        Route::prefix('psr')->name('psr.')->group(function () {

            // PSR module dashboard ( psrDashboard)
            Route::middleware('permission:clinical.psr.view')->group(function () {
                Route::get('/', [\App\Http\Controllers\DashboardController::class, 'psrDashboard'])->name('dashboard');
            });

            // Admissions ────────────────────────────────────────
            Route::middleware('permission:clinical.psr.admissions.view')->group(function () {
                Route::get('admissions', [PsrAdmissionController::class, 'index'])->name('admissions.index');
            });
            Route::middleware('permission:clinical.psr.admissions.create')->group(function () {
                Route::get('admissions/create', [PsrAdmissionController::class, 'create'])->name('admissions.create');
                Route::post('admissions',       [PsrAdmissionController::class, 'store'])->name('admissions.store');
            });
            Route::middleware('permission:clinical.psr.admissions.view')->group(function () {
                Route::get('admissions/{admission}', [PsrAdmissionController::class, 'show'])->name('admissions.show');
            });
            Route::middleware('permission:clinical.psr.admissions.edit')->group(function () {
                Route::get('admissions/{admission}/edit', [PsrAdmissionController::class, 'edit'])->name('admissions.edit');
                Route::put('admissions/{admission}',      [PsrAdmissionController::class, 'update'])->name('admissions.update');
                Route::post('admissions/{admission}/transition', [PsrAdmissionController::class, 'transitionStatus'])->name('admissions.transition');
            });
            Route::middleware('permission:clinical.psr.admissions.delete')->group(function () {
                Route::delete('admissions/{admission}', [PsrAdmissionController::class, 'destroy'])->name('admissions.destroy');
            });

            // Intake (1:1 with the admission) ────────────────────
            Route::middleware('permission:clinical.psr.admissions.edit')->group(function () {
                Route::get('intakes/create',        [PsrIntakeController::class, 'create'])->name('intakes.create');
                Route::post('intakes',              [PsrIntakeController::class, 'store'])->name('intakes.store');
                Route::get('intakes/{intake}/edit', [PsrIntakeController::class, 'edit'])->name('intakes.edit');
                Route::put('intakes/{intake}',      [PsrIntakeController::class, 'update'])->name('intakes.update');
                Route::post('intakes/{intake}/sign', [PsrIntakeController::class, 'sign'])->name('intakes.sign');
                Route::delete('intakes/{intake}',   [PsrIntakeController::class, 'destroy'])->name('intakes.destroy');
            });

            // Group sessions ─────────────────────────────────────
            Route::middleware('permission:clinical.psr.group_sessions.view')->group(function () {
                Route::get('group-sessions', [PsrGroupSessionController::class, 'index'])->name('group_sessions.index');
            });
            Route::middleware('permission:clinical.psr.group_sessions.create')->group(function () {
                Route::get('group-sessions/create', [PsrGroupSessionController::class, 'create'])->name('group_sessions.create');
                Route::post('group-sessions',       [PsrGroupSessionController::class, 'store'])->name('group_sessions.store');
            });
            Route::middleware('permission:clinical.psr.group_sessions.view')->group(function () {
                Route::get('group-sessions/{groupSession}', [PsrGroupSessionController::class, 'show'])->name('group_sessions.show');
            });
            Route::middleware('permission:clinical.psr.group_sessions.edit')->group(function () {
                Route::get('group-sessions/{groupSession}/edit', [PsrGroupSessionController::class, 'edit'])->name('group_sessions.edit');
                Route::put('group-sessions/{groupSession}',      [PsrGroupSessionController::class, 'update'])->name('group_sessions.update');
            });
            Route::middleware('permission:clinical.psr.group_sessions.delete')->group(function () {
                Route::delete('group-sessions/{groupSession}', [PsrGroupSessionController::class, 'destroy'])->name('group_sessions.destroy');
            });

            // Assessments (Bio + FARS) ───────────────────────────
            Route::middleware('permission:clinical.psr.assessments.view')->group(function () {
                Route::get('assessments', [PsrAssessmentController::class, 'index'])->name('assessments.index');
            });
            Route::middleware('permission:clinical.psr.assessments.create')->group(function () {
                Route::get('assessments/create', [PsrAssessmentController::class, 'create'])->name('assessments.create');
                Route::post('assessments',       [PsrAssessmentController::class, 'store'])->name('assessments.store');
                Route::get('admissions/{admission}/fars/create', [PsrAssessmentController::class, 'farsCreate'])->name('assessments.fars.create');
                Route::post('admissions/{admission}/fars',       [PsrAssessmentController::class, 'farsStore'])->name('assessments.fars.store');
            });
            Route::middleware('permission:clinical.psr.assessments.view')->group(function () {
                Route::get('assessments/{assessment}', [PsrAssessmentController::class, 'show'])->name('assessments.show');
            });
            Route::middleware('permission:clinical.psr.assessments.edit')->group(function () {
                Route::get('assessments/{assessment}/edit', [PsrAssessmentController::class, 'edit'])->name('assessments.edit');
                Route::put('assessments/{assessment}',      [PsrAssessmentController::class, 'update'])->name('assessments.update');
                Route::get('fars/{fars}/edit', [PsrAssessmentController::class, 'farsEdit'])->name('assessments.fars.edit');
                Route::put('fars/{fars}',      [PsrAssessmentController::class, 'farsUpdate'])->name('assessments.fars.update');
            });
            Route::middleware('permission:clinical.psr.assessments.sign')->group(function () {
                Route::post('assessments/{assessment}/sign', [PsrAssessmentController::class, 'sign'])->name('assessments.sign');
                Route::post('fars/{fars}/sign', [PsrAssessmentController::class, 'farsSign'])->name('assessments.fars.sign');
            });
            Route::middleware('permission:clinical.psr.assessments.delete')->group(function () {
                Route::delete('assessments/{assessment}', [PsrAssessmentController::class, 'destroy'])->name('assessments.destroy');
                Route::delete('fars/{fars}',              [PsrAssessmentController::class, 'farsDestroy'])->name('assessments.fars.destroy');
            });

            // Authorizations ─────────────────────────────────────
            Route::middleware('permission:clinical.psr.authorizations.view')->group(function () {
                Route::get('authorizations', [PsrAuthorizationController::class, 'index'])->name('authorizations.index');
            });
            Route::middleware('permission:clinical.psr.authorizations.create')->group(function () {
                Route::get('authorizations/create', [PsrAuthorizationController::class, 'create'])->name('authorizations.create');
                Route::post('authorizations',       [PsrAuthorizationController::class, 'store'])->name('authorizations.store');
            });
            Route::middleware('permission:clinical.psr.authorizations.view')->group(function () {
                Route::get('authorizations/{authorization}', [PsrAuthorizationController::class, 'show'])->name('authorizations.show');
            });
            Route::middleware('permission:clinical.psr.authorizations.edit')->group(function () {
                Route::get('authorizations/{authorization}/edit', [PsrAuthorizationController::class, 'edit'])->name('authorizations.edit');
                Route::put('authorizations/{authorization}',      [PsrAuthorizationController::class, 'update'])->name('authorizations.update');
            });
            Route::middleware('permission:clinical.psr.authorizations.delete')->group(function () {
                Route::delete('authorizations/{authorization}', [PsrAuthorizationController::class, 'destroy'])->name('authorizations.destroy');
            });

            // Treatment plans ────────────────────────────────────
            Route::middleware('permission:clinical.psr.treatment_plans.view')->group(function () {
                Route::get('treatment-plans', [PsrTreatmentPlanController::class, 'index'])->name('treatment_plans.index');
            });
            Route::middleware('permission:clinical.psr.treatment_plans.create')->group(function () {
                Route::get('treatment-plans/create', [PsrTreatmentPlanController::class, 'create'])->name('treatment_plans.create');
                Route::post('treatment-plans',       [PsrTreatmentPlanController::class, 'store'])->name('treatment_plans.store');
            });
            Route::middleware('permission:clinical.psr.treatment_plans.view')->group(function () {
                Route::get('treatment-plans/{treatmentPlan}', [PsrTreatmentPlanController::class, 'show'])->name('treatment_plans.show');
            });
            Route::middleware('permission:clinical.psr.treatment_plans.edit')->group(function () {
                Route::get('treatment-plans/{treatmentPlan}/edit', [PsrTreatmentPlanController::class, 'edit'])->name('treatment_plans.edit');
                Route::put('treatment-plans/{treatmentPlan}',      [PsrTreatmentPlanController::class, 'update'])->name('treatment_plans.update');
            });
            Route::middleware('permission:clinical.psr.treatment_plans.sign')->group(function () {
                Route::post('treatment-plans/{treatmentPlan}/sign', [PsrTreatmentPlanController::class, 'sign'])->name('treatment_plans.sign');
            });
            Route::middleware('permission:clinical.psr.treatment_plans.delete')->group(function () {
                Route::delete('treatment-plans/{treatmentPlan}', [PsrTreatmentPlanController::class, 'destroy'])->name('treatment_plans.destroy');
            });
            Route::middleware('permission:clinical.psr.treatment_plans.edit')->group(function () {
                Route::post('admissions/{admission}/treatment-plan/ai-suggest-goals', [PsrTreatmentPlanController::class, 'aiSuggestGoals'])
                    ->name('treatment_plans.ai_suggest_goals');
            });

            // Progress notes ─────────────────────────────────────
            Route::middleware('permission:clinical.psr.progress_notes.view')->group(function () {
                Route::get('progress-notes', [PsrProgressNoteController::class, 'index'])->name('progress_notes.index');
            });
            Route::middleware('permission:clinical.psr.progress_notes.create')->group(function () {
                Route::get('progress-notes/create', [PsrProgressNoteController::class, 'create'])->name('progress_notes.create');
                Route::post('progress-notes',       [PsrProgressNoteController::class, 'store'])->name('progress_notes.store');
            });
            Route::middleware('permission:clinical.psr.progress_notes.view')->group(function () {
                Route::get('progress-notes/{progressNote}', [PsrProgressNoteController::class, 'show'])->name('progress_notes.show');
            });
            Route::middleware('permission:clinical.psr.progress_notes.edit')->group(function () {
                Route::get('progress-notes/{progressNote}/edit', [PsrProgressNoteController::class, 'edit'])->name('progress_notes.edit');
                Route::put('progress-notes/{progressNote}',      [PsrProgressNoteController::class, 'update'])->name('progress_notes.update');
            });
            Route::middleware('permission:clinical.psr.progress_notes.sign')->group(function () {
                Route::post('progress-notes/{progressNote}/sign',     [PsrProgressNoteController::class, 'sign'])->name('progress_notes.sign');
                Route::post('progress-notes/{progressNote}/addendum', [PsrProgressNoteController::class, 'addendum'])->name('progress_notes.addendum');
            });
            Route::middleware('permission:clinical.psr.progress_notes.delete')->group(function () {
                Route::delete('progress-notes/{progressNote}', [PsrProgressNoteController::class, 'destroy'])->name('progress_notes.destroy');
            });
            Route::middleware('permission:clinical.psr.progress_notes.edit')->group(function () {
                Route::post('progress-notes/ai-suggest', [PsrProgressNoteController::class, 'aiSuggest'])->name('progress_notes.ai_suggest');
            });

            // Service log ────────────────────────────────────────
            Route::middleware('permission:clinical.psr.service_log.view')->group(function () {
                Route::get('service-log', [PsrServiceLogController::class, 'index'])->name('service_log.index');
            });
            Route::middleware('permission:clinical.psr.service_log.create')->group(function () {
                Route::get('service-log/create', [PsrServiceLogController::class, 'create'])->name('service_log.create');
                Route::post('service-log',       [PsrServiceLogController::class, 'store'])->name('service_log.store');
            });
            Route::middleware('permission:clinical.psr.service_log.view')->group(function () {
                Route::get('service-log/{serviceLog}', [PsrServiceLogController::class, 'show'])->name('service_log.show');
            });
            Route::middleware('permission:clinical.psr.service_log.edit')->group(function () {
                Route::get('service-log/{serviceLog}/edit', [PsrServiceLogController::class, 'edit'])->name('service_log.edit');
                Route::put('service-log/{serviceLog}',      [PsrServiceLogController::class, 'update'])->name('service_log.update');
            });
            Route::middleware('permission:clinical.psr.service_log.delete')->group(function () {
                Route::delete('service-log/{serviceLog}', [PsrServiceLogController::class, 'destroy'])->name('service_log.destroy');
            });

            // Superbill ──────────────────────────────────────────
            Route::middleware('permission:clinical.psr.superbill.view')->group(function () {
                Route::get('superbill', [PsrSuperbillController::class, 'index'])->name('superbill.index');
            });
            Route::middleware('permission:clinical.psr.superbill.lock')->group(function () {
                Route::post('superbill/lock',         [PsrSuperbillController::class, 'lock'])->name('superbill.lock');
                Route::delete('superbill/lock/{lock}', [PsrSuperbillController::class, 'unlock'])->name('superbill.unlock');
            });

            // Discharge summaries ────────────────────────────────
            Route::middleware('permission:clinical.psr.discharges.view')->group(function () {
                Route::get('discharges', [PsrDischargeController::class, 'index'])->name('discharges.index');
            });
            Route::middleware('permission:clinical.psr.discharges.create')->group(function () {
                Route::get('discharges/create', [PsrDischargeController::class, 'create'])->name('discharges.create');
                Route::post('discharges',       [PsrDischargeController::class, 'store'])->name('discharges.store');
            });
            Route::middleware('permission:clinical.psr.discharges.view')->group(function () {
                Route::get('discharges/{discharge}', [PsrDischargeController::class, 'show'])->name('discharges.show');
            });
            Route::middleware('permission:clinical.psr.discharges.edit')->group(function () {
                Route::get('discharges/{discharge}/edit', [PsrDischargeController::class, 'edit'])->name('discharges.edit');
                Route::put('discharges/{discharge}',      [PsrDischargeController::class, 'update'])->name('discharges.update');
            });
            Route::middleware('permission:clinical.psr.discharges.sign')->group(function () {
                Route::post('discharges/{discharge}/sign', [PsrDischargeController::class, 'sign'])->name('discharges.sign');
            });
            Route::middleware('permission:clinical.psr.discharges.delete')->group(function () {
                Route::delete('discharges/{discharge}', [PsrDischargeController::class, 'destroy'])->name('discharges.destroy');
            });
        });

        Route::prefix('it')->name('it.')->group(function () {
            Route::middleware('permission:clinical.it.view')->group(function () {
                Route::get('/',          [ItAdmissionController::class, 'dashboard'])->name('dashboard');
                Route::get('admissions', [ItAdmissionController::class, 'index'])->name('admissions.index');
                Route::get('sessions',   [ItSessionController::class,   'index'])->name('sessions.index');
            });
            Route::middleware('permission:clinical.it.create')->group(function () {
                Route::get('admissions/create',                       [ItAdmissionController::class, 'create'])->name('admissions.create');
                Route::post('admissions',                             [ItAdmissionController::class, 'store'])->name('admissions.store');
                // Standalone session create (patient picker) — reachable from the cross-patient list.
                Route::get('sessions/create',                         [ItSessionController::class, 'createAny'])->name('sessions.create_any');
                Route::post('sessions',                               [ItSessionController::class, 'storeAny'])->name('sessions.store_any');
                Route::get('admissions/{admission}/sessions/create',  [ItSessionController::class, 'create'])->name('sessions.create');
                Route::post('admissions/{admission}/sessions',        [ItSessionController::class, 'store'])->name('sessions.store');
            });
            Route::middleware('permission:clinical.it.view')->group(function () {
                Route::get('admissions/{admission}', [ItAdmissionController::class, 'show'])->name('admissions.show');
                Route::get('admissions/{admission}/sessions/{session}', [ItSessionController::class, 'show'])->name('sessions.show');
            });
            Route::middleware('permission:clinical.it.edit')->group(function () {
                Route::get('admissions/{admission}/edit', [ItAdmissionController::class, 'edit'])->name('admissions.edit');
                Route::put('admissions/{admission}',      [ItAdmissionController::class, 'update'])->name('admissions.update');
                Route::get('admissions/{admission}/sessions/{session}/edit', [ItSessionController::class, 'edit'])->name('sessions.edit');
                Route::put('admissions/{admission}/sessions/{session}',      [ItSessionController::class, 'update'])->name('sessions.update');
            });
            Route::middleware('permission:clinical.it.delete')->group(function () {
                Route::delete('admissions/{admission}',                   [ItAdmissionController::class, 'destroy'])->name('admissions.destroy');
                Route::delete('admissions/{admission}/sessions/{session}', [ItSessionController::class, 'destroy'])->name('sessions.destroy');
            });

            // IT Treatment plans
            Route::middleware('permission:clinical.it.treatment_plans.view')->group(function () {
                Route::get('treatment-plans', [ItTreatmentPlanController::class, 'index'])->name('treatment_plans.index');
                Route::get('treatment-plans/{treatmentPlan}', [ItTreatmentPlanController::class, 'show'])->whereNumber('treatmentPlan')->name('treatment_plans.show');
            });
            Route::middleware('permission:clinical.it.treatment_plans.create')->group(function () {
                Route::get('treatment-plans/create', [ItTreatmentPlanController::class, 'create'])->name('treatment_plans.create');
                Route::post('treatment-plans',       [ItTreatmentPlanController::class, 'store'])->name('treatment_plans.store');
            });
            Route::middleware('permission:clinical.it.treatment_plans.edit')->group(function () {
                Route::get('treatment-plans/{treatmentPlan}/edit', [ItTreatmentPlanController::class, 'edit'])->name('treatment_plans.edit');
                Route::put('treatment-plans/{treatmentPlan}',      [ItTreatmentPlanController::class, 'update'])->name('treatment_plans.update');
                Route::post('admissions/{admission}/treatment-plan/ai-suggest-goals', [ItTreatmentPlanController::class, 'aiSuggestGoals'])->name('treatment_plans.ai_suggest_goals');
            });
            Route::middleware('permission:clinical.it.treatment_plans.sign')->group(function () {
                Route::post('treatment-plans/{treatmentPlan}/sign', [ItTreatmentPlanController::class, 'sign'])->name('treatment_plans.sign');
            });
            Route::middleware('permission:clinical.it.treatment_plans.delete')->group(function () {
                Route::delete('treatment-plans/{treatmentPlan}', [ItTreatmentPlanController::class, 'destroy'])->name('treatment_plans.destroy');
            });

            // IT Authorizations
            Route::middleware('permission:clinical.it.authorizations.view')->group(function () {
                Route::get('authorizations', [ItAuthorizationController::class, 'index'])->name('authorizations.index');
                Route::get('authorizations/{authorization}', [ItAuthorizationController::class, 'show'])->whereNumber('authorization')->name('authorizations.show');
            });
            Route::middleware('permission:clinical.it.authorizations.create')->group(function () {
                Route::get('authorizations/create', [ItAuthorizationController::class, 'create'])->name('authorizations.create');
                Route::post('authorizations',       [ItAuthorizationController::class, 'store'])->name('authorizations.store');
            });
            Route::middleware('permission:clinical.it.authorizations.edit')->group(function () {
                Route::get('authorizations/{authorization}/edit', [ItAuthorizationController::class, 'edit'])->name('authorizations.edit');
                Route::put('authorizations/{authorization}',      [ItAuthorizationController::class, 'update'])->name('authorizations.update');
            });
            Route::middleware('permission:clinical.it.authorizations.delete')->group(function () {
                Route::delete('authorizations/{authorization}', [ItAuthorizationController::class, 'destroy'])->name('authorizations.destroy');
            });

            // IT Service log
            Route::middleware('permission:clinical.it.service_log.view')->group(function () {
                Route::get('service-log', [ItServiceLogController::class, 'index'])->name('service_log.index');
                Route::get('service-log/{serviceLog}', [ItServiceLogController::class, 'show'])->whereNumber('serviceLog')->name('service_log.show');
            });
            Route::middleware('permission:clinical.it.service_log.create')->group(function () {
                Route::get('service-log/create', [ItServiceLogController::class, 'create'])->name('service_log.create');
                Route::post('service-log',       [ItServiceLogController::class, 'store'])->name('service_log.store');
            });
            Route::middleware('permission:clinical.it.service_log.edit')->group(function () {
                Route::get('service-log/{serviceLog}/edit', [ItServiceLogController::class, 'edit'])->name('service_log.edit');
                Route::put('service-log/{serviceLog}',      [ItServiceLogController::class, 'update'])->name('service_log.update');
            });
            Route::middleware('permission:clinical.it.service_log.delete')->group(function () {
                Route::delete('service-log/{serviceLog}', [ItServiceLogController::class, 'destroy'])->name('service_log.destroy');
            });

            // IT Superbill
            Route::middleware('permission:clinical.it.superbill.view')->group(function () {
                Route::get('superbill', [ItSuperbillController::class, 'index'])->name('superbill.index');
            });
            Route::middleware('permission:clinical.it.superbill.lock')->group(function () {
                Route::post('superbill/lock',         [ItSuperbillController::class, 'lock'])->name('superbill.lock');
                Route::delete('superbill/lock/{lock}', [ItSuperbillController::class, 'unlock'])->name('superbill.unlock');
            });

            // IT Discharges
            Route::middleware('permission:clinical.it.discharges.view')->group(function () {
                Route::get('discharges', [ItDischargeController::class, 'index'])->name('discharges.index');
                Route::get('discharges/{discharge}', [ItDischargeController::class, 'show'])->whereNumber('discharge')->name('discharges.show');
            });
            Route::middleware('permission:clinical.it.discharges.create')->group(function () {
                Route::get('discharges/create', [ItDischargeController::class, 'create'])->name('discharges.create');
                Route::post('discharges',       [ItDischargeController::class, 'store'])->name('discharges.store');
            });
            Route::middleware('permission:clinical.it.discharges.edit')->group(function () {
                Route::get('discharges/{discharge}/edit', [ItDischargeController::class, 'edit'])->name('discharges.edit');
                Route::put('discharges/{discharge}',      [ItDischargeController::class, 'update'])->name('discharges.update');
            });
            Route::middleware('permission:clinical.it.discharges.sign')->group(function () {
                Route::post('discharges/{discharge}/sign', [ItDischargeController::class, 'sign'])->name('discharges.sign');
            });
            Route::middleware('permission:clinical.it.discharges.delete')->group(function () {
                Route::delete('discharges/{discharge}', [ItDischargeController::class, 'destroy'])->name('discharges.destroy');
            });
        });

        Route::prefix('tcm')->name('tcm.')->group(function () {
            Route::middleware('permission:clinical.tcm.view')->group(function () {
                Route::get('/',          [TcmAdmissionController::class, 'dashboard'])->name('dashboard');
                Route::get('admissions', [TcmAdmissionController::class, 'index'])->name('admissions.index');
                Route::get('contacts',   [TcmContactController::class,   'index'])->name('contacts.index');
            });
            Route::middleware('permission:clinical.tcm.create')->group(function () {
                Route::get('admissions/create',                        [TcmAdmissionController::class, 'create'])->name('admissions.create');
                Route::post('admissions',                              [TcmAdmissionController::class, 'store'])->name('admissions.store');
                // Standalone contact create (patient picker) — reachable from the cross-patient list.
                Route::get('contacts/create',                          [TcmContactController::class, 'createAny'])->name('contacts.create_any');
                Route::post('contacts',                                [TcmContactController::class, 'storeAny'])->name('contacts.store_any');
                Route::get('admissions/{admission}/contacts/create',   [TcmContactController::class, 'create'])->name('contacts.create');
                Route::post('admissions/{admission}/contacts',         [TcmContactController::class, 'store'])->name('contacts.store');
            });
            Route::middleware('permission:clinical.tcm.view')->group(function () {
                Route::get('admissions/{admission}', [TcmAdmissionController::class, 'show'])->name('admissions.show');
                Route::get('admissions/{admission}/contacts/{contact}', [TcmContactController::class, 'show'])->name('contacts.show');
            });
            Route::middleware('permission:clinical.tcm.edit')->group(function () {
                Route::get('admissions/{admission}/edit', [TcmAdmissionController::class, 'edit'])->name('admissions.edit');
                Route::put('admissions/{admission}',      [TcmAdmissionController::class, 'update'])->name('admissions.update');
                Route::get('admissions/{admission}/contacts/{contact}/edit', [TcmContactController::class, 'edit'])->name('contacts.edit');
                Route::put('admissions/{admission}/contacts/{contact}',      [TcmContactController::class, 'update'])->name('contacts.update');
            });
            Route::middleware('permission:clinical.tcm.delete')->group(function () {
                Route::delete('admissions/{admission}',                   [TcmAdmissionController::class, 'destroy'])->name('admissions.destroy');
                Route::delete('admissions/{admission}/contacts/{contact}', [TcmContactController::class, 'destroy'])->name('contacts.destroy');
            });

            // TCM Progress notes
            Route::middleware('permission:clinical.tcm.progress_notes.view')->group(function () {
                Route::get('progress-notes', [TcmProgressNoteController::class, 'index'])->name('progress_notes.index');
            });
            Route::middleware('permission:clinical.tcm.progress_notes.create')->group(function () {
                Route::get('progress-notes/create', [TcmProgressNoteController::class, 'create'])->name('progress_notes.create');
                Route::post('progress-notes',       [TcmProgressNoteController::class, 'store'])->name('progress_notes.store');
            });
            Route::middleware('permission:clinical.tcm.progress_notes.view')->group(function () {
                Route::get('progress-notes/{progressNote}', [TcmProgressNoteController::class, 'show'])->whereNumber('progressNote')->name('progress_notes.show');
            });
            Route::middleware('permission:clinical.tcm.progress_notes.edit')->group(function () {
                Route::get('progress-notes/{progressNote}/edit', [TcmProgressNoteController::class, 'edit'])->name('progress_notes.edit');
                Route::put('progress-notes/{progressNote}',      [TcmProgressNoteController::class, 'update'])->name('progress_notes.update');
            });
            Route::middleware('permission:clinical.tcm.progress_notes.sign')->group(function () {
                Route::post('progress-notes/{progressNote}/sign',     [TcmProgressNoteController::class, 'sign'])->name('progress_notes.sign');
                Route::post('progress-notes/{progressNote}/addendum', [TcmProgressNoteController::class, 'addendum'])->name('progress_notes.addendum');
            });
            Route::middleware('permission:clinical.tcm.progress_notes.delete')->group(function () {
                Route::delete('progress-notes/{progressNote}', [TcmProgressNoteController::class, 'destroy'])->name('progress_notes.destroy');
            });

            // TCM Service plans
            Route::middleware('permission:clinical.tcm.treatment_plans.view')->group(function () {
                Route::get('treatment-plans', [TcmTreatmentPlanController::class, 'index'])->name('treatment_plans.index');
                Route::get('treatment-plans/{treatmentPlan}', [TcmTreatmentPlanController::class, 'show'])->whereNumber('treatmentPlan')->name('treatment_plans.show');
            });
            Route::middleware('permission:clinical.tcm.treatment_plans.create')->group(function () {
                Route::get('treatment-plans/create', [TcmTreatmentPlanController::class, 'create'])->name('treatment_plans.create');
                Route::post('treatment-plans',       [TcmTreatmentPlanController::class, 'store'])->name('treatment_plans.store');
            });
            Route::middleware('permission:clinical.tcm.treatment_plans.edit')->group(function () {
                Route::get('treatment-plans/{treatmentPlan}/edit', [TcmTreatmentPlanController::class, 'edit'])->name('treatment_plans.edit');
                Route::put('treatment-plans/{treatmentPlan}',      [TcmTreatmentPlanController::class, 'update'])->name('treatment_plans.update');
                Route::post('admissions/{admission}/treatment-plan/ai-suggest-goals', [TcmTreatmentPlanController::class, 'aiSuggestGoals'])->name('treatment_plans.ai_suggest_goals');
            });
            Route::middleware('permission:clinical.tcm.treatment_plans.sign')->group(function () {
                Route::post('treatment-plans/{treatmentPlan}/sign', [TcmTreatmentPlanController::class, 'sign'])->name('treatment_plans.sign');
            });
            Route::middleware('permission:clinical.tcm.treatment_plans.delete')->group(function () {
                Route::delete('treatment-plans/{treatmentPlan}', [TcmTreatmentPlanController::class, 'destroy'])->name('treatment_plans.destroy');
            });

            // TCM Authorizations
            Route::middleware('permission:clinical.tcm.authorizations.view')->group(function () {
                Route::get('authorizations', [TcmAuthorizationController::class, 'index'])->name('authorizations.index');
                Route::get('authorizations/{authorization}', [TcmAuthorizationController::class, 'show'])->whereNumber('authorization')->name('authorizations.show');
            });
            Route::middleware('permission:clinical.tcm.authorizations.create')->group(function () {
                Route::get('authorizations/create', [TcmAuthorizationController::class, 'create'])->name('authorizations.create');
                Route::post('authorizations',       [TcmAuthorizationController::class, 'store'])->name('authorizations.store');
            });
            Route::middleware('permission:clinical.tcm.authorizations.edit')->group(function () {
                Route::get('authorizations/{authorization}/edit', [TcmAuthorizationController::class, 'edit'])->name('authorizations.edit');
                Route::put('authorizations/{authorization}',      [TcmAuthorizationController::class, 'update'])->name('authorizations.update');
            });
            Route::middleware('permission:clinical.tcm.authorizations.delete')->group(function () {
                Route::delete('authorizations/{authorization}', [TcmAuthorizationController::class, 'destroy'])->name('authorizations.destroy');
            });

            // TCM Service log
            Route::middleware('permission:clinical.tcm.service_log.view')->group(function () {
                Route::get('service-log', [TcmServiceLogController::class, 'index'])->name('service_log.index');
                Route::get('service-log/{serviceLog}', [TcmServiceLogController::class, 'show'])->whereNumber('serviceLog')->name('service_log.show');
            });
            Route::middleware('permission:clinical.tcm.service_log.create')->group(function () {
                Route::get('service-log/create', [TcmServiceLogController::class, 'create'])->name('service_log.create');
                Route::post('service-log',       [TcmServiceLogController::class, 'store'])->name('service_log.store');
            });
            Route::middleware('permission:clinical.tcm.service_log.edit')->group(function () {
                Route::get('service-log/{serviceLog}/edit', [TcmServiceLogController::class, 'edit'])->name('service_log.edit');
                Route::put('service-log/{serviceLog}',      [TcmServiceLogController::class, 'update'])->name('service_log.update');
            });
            Route::middleware('permission:clinical.tcm.service_log.delete')->group(function () {
                Route::delete('service-log/{serviceLog}', [TcmServiceLogController::class, 'destroy'])->name('service_log.destroy');
            });

            // TCM Superbill
            Route::middleware('permission:clinical.tcm.superbill.view')->group(function () {
                Route::get('superbill', [TcmSuperbillController::class, 'index'])->name('superbill.index');
            });
            Route::middleware('permission:clinical.tcm.superbill.lock')->group(function () {
                Route::post('superbill/lock',         [TcmSuperbillController::class, 'lock'])->name('superbill.lock');
                Route::delete('superbill/lock/{lock}', [TcmSuperbillController::class, 'unlock'])->name('superbill.unlock');
            });

            // TCM Discharges
            Route::middleware('permission:clinical.tcm.discharges.view')->group(function () {
                Route::get('discharges', [TcmDischargeController::class, 'index'])->name('discharges.index');
                Route::get('discharges/{discharge}', [TcmDischargeController::class, 'show'])->whereNumber('discharge')->name('discharges.show');
            });
            Route::middleware('permission:clinical.tcm.discharges.create')->group(function () {
                Route::get('discharges/create', [TcmDischargeController::class, 'create'])->name('discharges.create');
                Route::post('discharges',       [TcmDischargeController::class, 'store'])->name('discharges.store');
            });
            Route::middleware('permission:clinical.tcm.discharges.edit')->group(function () {
                Route::get('discharges/{discharge}/edit', [TcmDischargeController::class, 'edit'])->name('discharges.edit');
                Route::put('discharges/{discharge}',      [TcmDischargeController::class, 'update'])->name('discharges.update');
            });
            Route::middleware('permission:clinical.tcm.discharges.sign')->group(function () {
                Route::post('discharges/{discharge}/sign', [TcmDischargeController::class, 'sign'])->name('discharges.sign');
            });
            Route::middleware('permission:clinical.tcm.discharges.delete')->group(function () {
                Route::delete('discharges/{discharge}', [TcmDischargeController::class, 'destroy'])->name('discharges.destroy');
            });
        });

        // PSR submodule routes are added in their own block (see G5 phase).
        // IT and TCM submodules will be added when those disciplines are
        // ported to dedicated tables (G7+).
    });
});
