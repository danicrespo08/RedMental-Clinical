<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Full catalog of permissions grouped by module. The Client Admin UI renders
     * this as a matrix (rows = roles, columns = permissions).
     *
     * Convention: "<module>.<resource>.<action>"
     */
    public const CATALOG = [
        'System' => [
            'system.users.view'   => 'View users',
            'system.users.create' => 'Create users',
            'system.users.edit'   => 'Edit users',
            'system.users.delete' => 'Delete users',
            'system.roles.view'   => 'View roles',
            'system.roles.manage' => 'Create / edit / delete roles',
        ],

        'HHRR — Patients' => [
            'hhrr.patients.view'   => 'View patients',
            'hhrr.patients.create' => 'Create patients',
            'hhrr.patients.edit'   => 'Edit patients',
            'hhrr.patients.delete' => 'Delete patients',
        ],
        'HHRR — Employees' => [
            'hhrr.employees.view'   => 'View employees',
            'hhrr.employees.create' => 'Create employees',
            'hhrr.employees.edit'   => 'Edit employees',
            'hhrr.employees.delete' => 'Delete employees',
        ],
        'HHRR — Departments' => [
            'hhrr.departments.view'   => 'View departments',
            'hhrr.departments.create' => 'Create departments',
            'hhrr.departments.edit'   => 'Edit departments',
            'hhrr.departments.delete' => 'Delete departments',
        ],
        'HHRR — Payers' => [
            'hhrr.payers.view'   => 'View payers',
            'hhrr.payers.create' => 'Create payers',
            'hhrr.payers.edit'   => 'Edit payers',
            'hhrr.payers.delete' => 'Delete payers',
        ],
        'HHRR — Clinics' => [
            'hhrr.clinics.view'   => 'View clinics',
            'hhrr.clinics.create' => 'Create clinics',
            'hhrr.clinics.edit'   => 'Edit clinics',
            'hhrr.clinics.delete' => 'Delete clinics',
        ],
        'System — Audit' => [
            'system.audit.view' => 'View system audit log',
        ],

        'Clinical — PSR (general)' => [
            'clinical.psr.view'   => 'View PSR module',
            'clinical.psr.create' => 'Create PSR records',
            'clinical.psr.edit'   => 'Edit PSR records',
            'clinical.psr.delete' => 'Delete PSR records',
        ],
        'Clinical — PSR Admissions'      => [
            'clinical.psr.admissions.view'    => 'View PSR admissions',
            'clinical.psr.admissions.create'  => 'Create PSR admissions',
            'clinical.psr.admissions.edit'    => 'Edit PSR admissions',
            'clinical.psr.admissions.delete'  => 'Delete PSR admissions',
        ],
        'Clinical — PSR Assessments' => [
            'clinical.psr.assessments.view'   => 'View PSR assessments (Bio + FARS)',
            'clinical.psr.assessments.create' => 'Create PSR assessments',
            'clinical.psr.assessments.edit'   => 'Edit PSR assessments',
            'clinical.psr.assessments.delete' => 'Delete PSR assessments',
            'clinical.psr.assessments.sign'   => 'Sign PSR assessments',
        ],
        'Clinical — PSR Authorizations' => [
            'clinical.psr.authorizations.view'   => 'View PSR insurance authorizations',
            'clinical.psr.authorizations.create' => 'Create PSR authorizations',
            'clinical.psr.authorizations.edit'   => 'Edit PSR authorizations',
            'clinical.psr.authorizations.delete' => 'Delete PSR authorizations',
        ],
        'Clinical — PSR Treatment plans' => [
            'clinical.psr.treatment_plans.view'   => 'View PSR treatment plans',
            'clinical.psr.treatment_plans.create' => 'Create PSR treatment plans',
            'clinical.psr.treatment_plans.edit'   => 'Edit PSR treatment plans',
            'clinical.psr.treatment_plans.delete' => 'Delete PSR treatment plans',
            'clinical.psr.treatment_plans.sign'   => 'Sign PSR treatment plans',
        ],
        'Clinical — PSR Progress notes' => [
            'clinical.psr.progress_notes.view'   => 'View PSR progress notes',
            'clinical.psr.progress_notes.create' => 'Create PSR progress notes',
            'clinical.psr.progress_notes.edit'   => 'Edit PSR progress notes',
            'clinical.psr.progress_notes.delete' => 'Delete PSR progress notes',
            'clinical.psr.progress_notes.sign'   => 'Sign and addendum PSR progress notes',
        ],
        'Clinical — PSR Service log' => [
            'clinical.psr.service_log.view'   => 'View PSR service log',
            'clinical.psr.service_log.create' => 'Create PSR service log entries',
            'clinical.psr.service_log.edit'   => 'Edit PSR service log entries',
            'clinical.psr.service_log.delete' => 'Delete PSR service log entries',
        ],
        'Clinical — PSR Superbill' => [
            'clinical.psr.superbill.view' => 'View PSR weekly superbill',
            'clinical.psr.superbill.lock' => 'Lock / unlock PSR superbill weeks',
        ],
        'Clinical — PSR Discharge' => [
            'clinical.psr.discharges.view'   => 'View PSR discharge summaries',
            'clinical.psr.discharges.create' => 'Create PSR discharge summaries',
            'clinical.psr.discharges.edit'   => 'Edit PSR discharge summaries',
            'clinical.psr.discharges.delete' => 'Delete PSR discharge summaries',
            'clinical.psr.discharges.sign'   => 'Sign PSR discharge summaries',
        ],
        'Clinical — PSR Group sessions' => [
            'clinical.psr.group_sessions.view'   => 'View PSR group sessions',
            'clinical.psr.group_sessions.create' => 'Create PSR group sessions',
            'clinical.psr.group_sessions.edit'   => 'Edit PSR group sessions',
            'clinical.psr.group_sessions.delete' => 'Delete PSR group sessions',
        ],
        'Clinical — IT' => [
            'clinical.it.view'   => 'View IT admissions / sessions',
            'clinical.it.create' => 'Create IT records',
            'clinical.it.edit'   => 'Edit IT records',
            'clinical.it.delete' => 'Delete IT records',
        ],
        'Clinical — IT Treatment plans' => [
            'clinical.it.treatment_plans.view'   => 'View IT treatment plans',
            'clinical.it.treatment_plans.create' => 'Create IT treatment plans',
            'clinical.it.treatment_plans.edit'   => 'Edit IT treatment plans',
            'clinical.it.treatment_plans.delete' => 'Delete IT treatment plans',
            'clinical.it.treatment_plans.sign'   => 'Sign IT treatment plans',
        ],
        'Clinical — IT Authorizations' => [
            'clinical.it.authorizations.view'   => 'View IT authorizations',
            'clinical.it.authorizations.create' => 'Create IT authorizations',
            'clinical.it.authorizations.edit'   => 'Edit IT authorizations',
            'clinical.it.authorizations.delete' => 'Delete IT authorizations',
        ],
        'Clinical — IT Service log' => [
            'clinical.it.service_log.view'   => 'View IT service log',
            'clinical.it.service_log.create' => 'Create IT service log entries',
            'clinical.it.service_log.edit'   => 'Edit IT service log entries',
            'clinical.it.service_log.delete' => 'Delete IT service log entries',
        ],
        'Clinical — IT Superbill' => [
            'clinical.it.superbill.view' => 'View IT superbill',
            'clinical.it.superbill.lock' => 'Lock IT superbill weeks',
        ],
        'Clinical — IT Discharges' => [
            'clinical.it.discharges.view'   => 'View IT discharge summaries',
            'clinical.it.discharges.create' => 'Create IT discharge summaries',
            'clinical.it.discharges.edit'   => 'Edit IT discharge summaries',
            'clinical.it.discharges.delete' => 'Delete IT discharge summaries',
            'clinical.it.discharges.sign'   => 'Sign IT discharge summaries',
        ],
        'Clinical — TCM' => [
            'clinical.tcm.view'   => 'View TCM admissions / contacts',
            'clinical.tcm.create' => 'Create TCM records',
            'clinical.tcm.edit'   => 'Edit TCM records',
            'clinical.tcm.delete' => 'Delete TCM records',
        ],
        'Clinical — TCM Service plans' => [
            'clinical.tcm.treatment_plans.view'   => 'View TCM service plans',
            'clinical.tcm.treatment_plans.create' => 'Create TCM service plans',
            'clinical.tcm.treatment_plans.edit'   => 'Edit TCM service plans',
            'clinical.tcm.treatment_plans.delete' => 'Delete TCM service plans',
            'clinical.tcm.treatment_plans.sign'   => 'Sign TCM service plans',
        ],
        'Clinical — TCM Authorizations' => [
            'clinical.tcm.authorizations.view'   => 'View TCM authorizations',
            'clinical.tcm.authorizations.create' => 'Create TCM authorizations',
            'clinical.tcm.authorizations.edit'   => 'Edit TCM authorizations',
            'clinical.tcm.authorizations.delete' => 'Delete TCM authorizations',
        ],
        'Clinical — TCM Service log' => [
            'clinical.tcm.service_log.view'   => 'View TCM service log',
            'clinical.tcm.service_log.create' => 'Create TCM service-log entries',
            'clinical.tcm.service_log.edit'   => 'Edit TCM service-log entries',
            'clinical.tcm.service_log.delete' => 'Delete TCM service-log entries',
        ],
        'Clinical — TCM Superbill' => [
            'clinical.tcm.superbill.view' => 'View TCM superbill',
            'clinical.tcm.superbill.lock' => 'Lock TCM superbill weeks',
        ],
        'Clinical — TCM Discharges' => [
            'clinical.tcm.discharges.view'   => 'View TCM discharge summaries',
            'clinical.tcm.discharges.create' => 'Create TCM discharge summaries',
            'clinical.tcm.discharges.edit'   => 'Edit TCM discharge summaries',
            'clinical.tcm.discharges.delete' => 'Delete TCM discharge summaries',
            'clinical.tcm.discharges.sign'   => 'Sign TCM discharge summaries',
        ],
    ];

    public function run(): void
    {
        foreach (self::CATALOG as $group) {
            foreach ($group as $name => $_description) {
                Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            }
        }

        // Seed the two built-in roles. Additional roles are created by each Client Admin.
        // Super Admin also gets the audit-view permission so they can inspect
        // every tenant's activity.
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web'])
            ->syncPermissions(['system.audit.view']);

        // Client Admin gets every permission — they are the full administrator
        // for their organization and should never be blocked by the permission
        // middleware.
        Role::firstOrCreate(['name' => 'Client Admin', 'guard_name' => 'web'])
            ->syncPermissions(Permission::all());

        // Clinical Admin — owns everything in the Clinical module (PSR/IT/TCM
        // and all submodules). Read-only access to HHRR patient / clinic /
        // employee data so clinical screens can resolve names and clinics.
        Role::firstOrCreate(['name' => 'Clinical Admin', 'guard_name' => 'web'])
            ->syncPermissions(
                Permission::where('name', 'like', 'clinical.%')
                    ->orWhereIn('name', [
                        'hhrr.patients.view',
                        'hhrr.clinics.view',
                        'hhrr.employees.view',
                        'system.audit.view',
                    ])
                    ->get()
            );
    }
}
