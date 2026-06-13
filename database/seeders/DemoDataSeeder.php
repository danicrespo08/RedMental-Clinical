<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Hhrr\Clinic;
use App\Models\Hhrr\Department;
use App\Models\Hhrr\Employee;
use App\Models\It\Admission   as ItAdmission;
use App\Models\It\Session     as ItSession;
use App\Models\Hhrr\Patient;
use App\Models\Hhrr\PatientInsurance;
use App\Models\Hhrr\Payer;
use App\Models\Psr\Admission             as PsrAdmission;
use App\Models\Psr\AssessmentBio         as PsrAssessmentBio;
use App\Models\Psr\Authorization         as PsrAuthorization;
use App\Models\Psr\DischargeSummary      as PsrDischargeSummary;
use App\Models\Psr\Fars                  as PsrFars;
use App\Models\Psr\Goal                  as PsrGoal;
use App\Models\Psr\GroupSession          as PsrGroupSession;
use App\Models\Psr\GroupSessionAttendee  as PsrGroupSessionAttendee;
use App\Models\Psr\Intake                as PsrIntake;
use App\Models\Psr\NoteTemplate          as PsrNoteTemplate;
use App\Models\Psr\Objective             as PsrObjective;
use App\Models\Psr\ProgressNote          as PsrProgressNote;
use App\Models\Psr\ServiceLog            as PsrServiceLog;
use App\Models\Psr\TreatmentPlan         as PsrTreatmentPlan;
use App\Models\Role;
use App\Models\Tcm\Admission  as TcmAdmission;
use App\Models\Tcm\Contact    as TcmContact;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $client = Client::where('name', 'Demo Behavioral Health')->first();
        if (! $client) {
            $this->command->warn('Demo client not found. Run DemoClientSeeder first.');
            return;
        }

        $admin = Department::firstOrCreate(
            ['client_id' => $client->id, 'name' => 'Administration'],
            ['code' => 'ADM', 'description' => 'Front-office administration, intake and reception duties.', 'active' => true]
        );
        $clin = Department::firstOrCreate(
            ['client_id' => $client->id, 'name' => 'Clinical'],
            ['code' => 'CLN', 'description' => 'Therapists, case managers, and direct-care clinical staff.', 'active' => true]
        );
        $bill = Department::firstOrCreate(
            ['client_id' => $client->id, 'name' => 'Billing'],
            ['code' => 'BIL', 'description' => 'Insurance billing, claims, AR follow-up.', 'active' => true]
        );

        $medicaid = Payer::firstOrCreate(
            ['client_id' => $client->id, 'name' => 'Florida Medicaid'],
            ['type' => 'Medicaid', 'edi_payer_id' => 'FLMCD', 'phone' => '(877) 254-1055', 'email' => 'provider@flmedicaid.gov',
             'address' => '2727 Mahan Drive', 'city' => 'Tallahassee', 'state' => 'FL', 'zip' => '32308',
             'notes' => 'Fee-for-service Florida Medicaid (AHCA).', 'active' => true]
        );
        $medicare = Payer::firstOrCreate(
            ['client_id' => $client->id, 'name' => 'Medicare Part B'],
            ['type' => 'Medicare', 'edi_payer_id' => '09102', 'phone' => '(866) 454-9007', 'email' => 'provider@medicare.fcso.com',
             'address' => '532 Riverside Ave', 'city' => 'Jacksonville', 'state' => 'FL', 'zip' => '32202',
             'notes' => 'First Coast Service Options — FL Part B MAC.', 'active' => true]
        );
        $aetna = Payer::firstOrCreate(
            ['client_id' => $client->id, 'name' => 'Aetna Commercial'],
            ['type' => 'Commercial', 'edi_payer_id' => '60054', 'phone' => '(800) 872-3862', 'email' => 'providers@aetna.com',
             'address' => '151 Farmington Ave', 'city' => 'Hartford', 'state' => 'CT', 'zip' => '06156',
             'notes' => 'Commercial PPO/HMO plans.', 'active' => true]
        );
        $bcbs = Payer::firstOrCreate(
            ['client_id' => $client->id, 'name' => 'Blue Cross Blue Shield of Florida'],
            ['type' => 'Commercial', 'edi_payer_id' => '00590', 'phone' => '(800) 352-2583', 'email' => 'providers@floridablue.com',
             'address' => '4800 Deerwood Campus Pkwy', 'city' => 'Jacksonville', 'state' => 'FL', 'zip' => '32246',
             'notes' => 'Florida Blue — BCBS of Florida.', 'active' => true]
        );
        $self = Payer::firstOrCreate(
            ['client_id' => $client->id, 'name' => 'Self-Pay'],
            ['type' => 'Self-Pay', 'edi_payer_id' => 'SELF', 'phone' => '(305) 555-0100', 'email' => 'billing@demo-bh.local',
             'address' => '500 NW 2nd Ave', 'city' => 'Miami', 'state' => 'FL', 'zip' => '33128',
             'notes' => 'Patient-direct billing — no insurance involved.', 'active' => true]
        );

        $miami = Clinic::firstOrCreate(
            ['client_id' => $client->id, 'name' => 'Miami Main Clinic'],
            ['code' => 'MIA', 'address' => '100 Brickell Ave', 'city' => 'Miami', 'state' => 'FL', 'zip' => '33131',
             'latitude' => 25.7617, 'longitude' => -80.1918,
             'phone' => '(305) 555-1000', 'email' => 'miami@demo-bh.local', 'active' => true]
        );
        $hialeah = Clinic::firstOrCreate(
            ['client_id' => $client->id, 'name' => 'Hialeah Branch'],
            ['code' => 'HIA', 'address' => '200 W 49th St', 'city' => 'Hialeah', 'state' => 'FL', 'zip' => '33012',
             'latitude' => 25.8576, 'longitude' => -80.2781,
             'phone' => '(305) 555-2000', 'email' => 'hialeah@demo-bh.local', 'active' => true]
        );
        $kendall = Clinic::firstOrCreate(
            ['client_id' => $client->id, 'name' => 'Kendall Outpatient'],
            ['code' => 'KEN', 'address' => '300 SW 88th St', 'city' => 'Kendall', 'state' => 'FL', 'zip' => '33176',
             'latitude' => 25.6793, 'longitude' => -80.3173,
             'phone' => '(305) 555-3000', 'email' => 'kendall@demo-bh.local', 'active' => true]
        );
        $clinicList = [$miami, $hialeah, $kendall];

        $therapistRole = Role::firstOrCreate(
            ['client_id' => $client->id, 'name' => 'Therapist', 'guard_name' => 'web']
        );
        $therapistRole->syncPermissions([
            'hhrr.patients.view', 'hhrr.patients.edit',
            'hhrr.clinics.view',
            'clinical.psr.view', 'clinical.psr.create', 'clinical.psr.edit',
            'clinical.psr.admissions.view', 'clinical.psr.admissions.create', 'clinical.psr.admissions.edit',
            'clinical.psr.assessments.view', 'clinical.psr.assessments.create', 'clinical.psr.assessments.edit', 'clinical.psr.assessments.sign',
            'clinical.psr.authorizations.view', 'clinical.psr.authorizations.create', 'clinical.psr.authorizations.edit',
            'clinical.psr.treatment_plans.view', 'clinical.psr.treatment_plans.create', 'clinical.psr.treatment_plans.edit', 'clinical.psr.treatment_plans.sign',
            'clinical.psr.progress_notes.view', 'clinical.psr.progress_notes.create', 'clinical.psr.progress_notes.edit', 'clinical.psr.progress_notes.sign',
            'clinical.psr.service_log.view', 'clinical.psr.service_log.create', 'clinical.psr.service_log.edit',
            'clinical.psr.superbill.view',
            'clinical.psr.discharges.view', 'clinical.psr.discharges.create', 'clinical.psr.discharges.edit', 'clinical.psr.discharges.sign',
            'clinical.psr.group_sessions.view', 'clinical.psr.group_sessions.create', 'clinical.psr.group_sessions.edit',
            'clinical.it.view',  'clinical.it.create',  'clinical.it.edit',
            'clinical.it.treatment_plans.view', 'clinical.it.treatment_plans.create', 'clinical.it.treatment_plans.edit', 'clinical.it.treatment_plans.sign',
            'clinical.it.authorizations.view', 'clinical.it.authorizations.create', 'clinical.it.authorizations.edit',
            'clinical.it.service_log.view', 'clinical.it.service_log.create', 'clinical.it.service_log.edit',
            'clinical.it.superbill.view', 'clinical.it.superbill.lock',
            'clinical.it.discharges.view', 'clinical.it.discharges.create', 'clinical.it.discharges.edit', 'clinical.it.discharges.sign',
            'clinical.tcm.treatment_plans.view', 'clinical.tcm.treatment_plans.create', 'clinical.tcm.treatment_plans.edit', 'clinical.tcm.treatment_plans.sign',
            'clinical.tcm.authorizations.view', 'clinical.tcm.authorizations.create', 'clinical.tcm.authorizations.edit',
            'clinical.tcm.service_log.view', 'clinical.tcm.service_log.create', 'clinical.tcm.service_log.edit',
            'clinical.tcm.superbill.view', 'clinical.tcm.superbill.lock',
            'clinical.tcm.discharges.view', 'clinical.tcm.discharges.create', 'clinical.tcm.discharges.edit', 'clinical.tcm.discharges.sign',
        ]);

        $caseManagerRole = Role::firstOrCreate(
            ['client_id' => $client->id, 'name' => 'Case Manager', 'guard_name' => 'web']
        );
        $caseManagerRole->syncPermissions([
            'hhrr.patients.view', 'hhrr.patients.edit',
            'hhrr.clinics.view',
            'clinical.tcm.view', 'clinical.tcm.create', 'clinical.tcm.edit',
        ]);

        $billerRole = Role::firstOrCreate(
            ['client_id' => $client->id, 'name' => 'Biller', 'guard_name' => 'web']
        );
        $billerRole->syncPermissions([
            'hhrr.patients.view', 'hhrr.patients.edit',
            'hhrr.payers.view',   'hhrr.payers.create', 'hhrr.payers.edit',
            'hhrr.employees.view',
        ]);

        $receptionistRole = Role::firstOrCreate(
            ['client_id' => $client->id, 'name' => 'Receptionist', 'guard_name' => 'web']
        );
        $receptionistRole->syncPermissions([
            'hhrr.patients.view', 'hhrr.patients.create', 'hhrr.patients.edit',
        ]);

        $people = [
            ['first' => 'Carmen', 'last' => 'Rodriguez', 'dept' => $admin, 'pos' => 'Administrator',     'role' => null,              'rate' => 50, 'provider' => false, 'npi' => null,         'gender' => 'Female', 'dob' => '1980-04-12', 'hired_months_ago' => 36, 'addr' => '1500 Collins Ave',   'city' => 'Miami Beach', 'zip' => '33139'],
            ['first' => 'David',  'last' => 'Martinez',  'dept' => $clin,  'pos' => 'Lead therapist',    'role' => $therapistRole,    'rate' => 55, 'provider' => true,  'npi' => '1234567890', 'gender' => 'Male',   'dob' => '1978-08-22', 'hired_months_ago' => 30, 'addr' => '850 Brickell Bay Dr', 'city' => 'Miami',       'zip' => '33131'],
            ['first' => 'Laura',  'last' => 'Garcia',    'dept' => $clin,  'pos' => 'Therapist',         'role' => $therapistRole,    'rate' => 45, 'provider' => true,  'npi' => '1234567891', 'gender' => 'Female', 'dob' => '1985-11-07', 'hired_months_ago' => 18, 'addr' => '2200 NW 7th St',     'city' => 'Miami',       'zip' => '33125'],
            ['first' => 'Miguel', 'last' => 'Hernandez', 'dept' => $clin,  'pos' => 'Case manager',      'role' => $caseManagerRole,  'rate' => 38, 'provider' => true,  'npi' => '1234567892', 'gender' => 'Male',   'dob' => '1982-06-14', 'hired_months_ago' => 24, 'addr' => '500 W 49th St',      'city' => 'Hialeah',     'zip' => '33012'],
            ['first' => 'Sofia',  'last' => 'Lopez',     'dept' => $clin,  'pos' => 'Case manager',      'role' => $caseManagerRole,  'rate' => 38, 'provider' => true,  'npi' => '1234567893', 'gender' => 'Female', 'dob' => '1990-02-28', 'hired_months_ago' => 12, 'addr' => '900 SW 88th St',     'city' => 'Kendall',     'zip' => '33176'],
            ['first' => 'Jorge',  'last' => 'Perez',     'dept' => $bill,  'pos' => 'Billing specialist','role' => $billerRole,       'rate' => 32, 'provider' => false, 'npi' => null,         'gender' => 'Male',   'dob' => '1988-09-19', 'hired_months_ago' => 8,  'addr' => '350 NW 2nd Ave',     'city' => 'Miami',       'zip' => '33128'],
            ['first' => 'Ana',    'last' => 'Torres',    'dept' => $admin, 'pos' => 'Receptionist',      'role' => $receptionistRole, 'rate' => 22, 'provider' => false, 'npi' => null,         'gender' => 'Female', 'dob' => '1995-03-05', 'hired_months_ago' => 6,  'addr' => '1100 Biscayne Blvd', 'city' => 'Miami',       'zip' => '33132'],
        ];

        $employees = [];
        foreach ($people as $i => $p) {
            $emailPrefix = strtolower($p['first'] . '.' . $p['last']);
            $emp = Employee::firstOrCreate(
                ['client_id' => $client->id, 'first_name' => $p['first'], 'last_name' => $p['last']],
                [
                    'department_id'            => $p['dept']->id,
                    'employee_number'          => 'EMP-' . str_pad((string)($i+1), 3, '0', STR_PAD_LEFT),
                    'npi'                      => $p['npi'],
                    'position'                 => $p['pos'],
                    'is_provider'              => $p['provider'],
                    'hourly_rate'              => $p['rate'],
                    'salary'                   => round($p['rate'] * 2080, 2),
                    'hire_date'                => Carbon::now()->subMonths($p['hired_months_ago'])->toDateString(),
                    'termination_date'         => null,
                    'email'                    => $emailPrefix . '@demo-bh.local',
                    'phone'                    => '(305) 555-' . str_pad((string)(2000 + $i), 4, '0', STR_PAD_LEFT),
                    'date_of_birth'            => $p['dob'],
                    'gender'                   => $p['gender'],
                    'address'                  => $p['addr'],
                    'city'                     => $p['city'],
                    'state'                    => 'FL',
                    'zip'                      => $p['zip'],
                    'emergency_contact_name'   => $p['first'] === 'Carmen' ? 'Roberto Rodriguez' : ($p['first'] . ' Family'),
                    'emergency_contact_phone'  => '(305) 555-' . str_pad((string)(7000 + $i), 4, '0', STR_PAD_LEFT),
                    'notes'                    => "Demo {$p['pos']} record — used for thesis defense walkthrough.",
                    'active'                   => true,
                ]
            );
            $employees[strtolower($p['first'])] = $emp;

            if ($p['role']) {
                // Create a login for this employee
                $user = User::firstOrCreate(
                    ['email' => strtolower($p['first'] . '.' . $p['last']) . '@demo-bh.local'],
                    [
                        'client_id' => $client->id,
                        'name'      => $p['first'] . ' ' . $p['last'],
                        'password'  => Hash::make('password123'),
                        'active'    => true,
                    ]
                );
                $user->syncRoles([$p['role']->name]);
            }
        }

        $patientsData = [
            ['Juan',    'Antonio', 'Rivera',   'M', '1985-03-12', $medicaid, 'MCD0010001', 'GRP-MCD-A', 'English', 'PSR'],
            ['Maria',   'Elena',   'Sanchez',  'F', '1990-07-22', $aetna,    'AET2024002',  'GRP-AET-1', 'Spanish', 'IT'],
            ['Pedro',   'Luis',    'Gonzalez', 'M', '1978-11-05', $medicaid, 'MCD0010003', 'GRP-MCD-A', 'Spanish', 'PSR'],
            ['Ana',     'Beatriz', 'Vargas',   'F', '2005-01-18', $medicaid, 'MCD0010004', 'GRP-MCD-A', 'English', 'TCM'],
            ['Roberto', 'Carlos',  'Fernandez','M', '1965-09-30', $medicare, 'MED99X0005', 'GRP-MED-B', 'Spanish', 'IT'],
            ['Luisa',   'Maria',   'Diaz',     'F', '1972-04-15', $bcbs,     'BCBS0FL006', 'GRP-BCBS-2','English', 'PSR'],
            ['Carlos',  'Andres',  'Mendoza',  'M', '1998-12-03', $medicaid, 'MCD0010007', 'GRP-MCD-A', 'Spanish', 'TCM'],
            ['Isabel',  'Sofia',   'Cruz',     'F', '1988-06-20', $aetna,    'AET2024008',  'GRP-AET-1', 'English', null],
            ['Miguel',  'Angel',   'Ortiz',    'M', '1995-08-14', $self,     'SELFPAY009',  'GRP-SELF',  'Spanish', null],
            ['Patricia','Lucia',   'Reyes',    'F', '2010-02-25', $medicaid, 'MCD0010010', 'GRP-MCD-A', 'English', null],
        ];

        $providers = [$employees['david'], $employees['laura'], $employees['miguel'], $employees['sofia']];

        // Plausible Miami-area lat/lng pool so the route planner has real
        // coordinates to optimize. Indexes line up with $patientsData.
        $patientCoords = [
            ['Miami',         25.7617, -80.1918],
            ['Hialeah',       25.8576, -80.2781],
            ['Kendall',       25.6793, -80.3173],
            ['Coral Gables',  25.7215, -80.2684],
            ['Doral',         25.8195, -80.3553],
            ['Aventura',      25.9565, -80.1392],
            ['Miami Beach',   25.7907, -80.1300],
            ['Homestead',     25.4687, -80.4776],
            ['North Miami',   25.8901, -80.1867],
            ['Cutler Bay',    25.5808, -80.3470],
        ];

        $patients = [];
        foreach ($patientsData as $i => [$first, $middle, $last, $sex, $dob, $payer, $policy, $group, $lang, $service]) {
            $intakeDate = Carbon::now()->subDays(($i + 1) * 15)->toDateString();
            $coord = $patientCoords[$i] ?? $patientCoords[0];
            $patient = Patient::firstOrCreate(
                ['client_id' => $client->id, 'first_name' => $first, 'last_name' => $last],
                [
                    'mrn'                      => 'MRN-' . str_pad((string)($i+1001), 4, '0', STR_PAD_LEFT),
                    'assigned_provider_id'     => $providers[$i % count($providers)]->id,
                    'middle_name'              => $middle,
                    'date_of_birth'            => $dob,
                    'gender'                   => $sex === 'M' ? 'Male' : 'Female',
                    'ssn'                      => '500-' . str_pad((string)(10 + $i), 2, '0', STR_PAD_LEFT) . '-' . str_pad((string)(1000 + $i * 7), 4, '0', STR_PAD_LEFT),
                    'phone'                    => '(305) 555-' . str_pad((string)(1000 + $i), 4, '0', STR_PAD_LEFT),
                    'email'                    => strtolower("{$first}.{$last}@patient.local"),
                    'address'                  => (100 + $i * 50) . ' ' . ['Main', 'Oak', 'Maple', 'Cedar', 'Palm', 'Coral'][$i % 6] . ' St',
                    'city'                     => $coord[0],
                    'state'                    => 'FL',
                    'zip'                      => '3310' . ($i % 9),
                    'latitude'                 => $coord[1],
                    'longitude'                => $coord[2],
                    'emergency_contact_name'   => $first . "'s Family Contact",
                    'emergency_contact_phone'  => '(305) 555-' . str_pad((string)(8000 + $i), 4, '0', STR_PAD_LEFT),
                    'preferred_language'       => $lang,
                    'intake_date'              => $intakeDate,
                    'notes'                    => "Demo patient — primary service interest: " . ($service ?: 'general intake'),
                    'active'                   => true,
                ]
            );

            // Enroll patient in 1-2 clinics rotating through the available list
            $clinic = $clinicList[$i % count($clinicList)];
            $patient->clinics()->syncWithoutDetaching([
                $clinic->id => ['enrollment_date' => $intakeDate, 'status' => 'active', 'notes' => "Initial enrollment at {$clinic->name}."],
            ]);

            if ($payer && $policy) {
                PatientInsurance::firstOrCreate(
                    ['patient_id' => $patient->id, 'payer_id' => $payer->id, 'priority' => 'primary'],
                    [
                        'policy_number'           => $policy,
                        'group_number'            => $group,
                        'subscriber_name'         => $patient->full_name,
                        'subscriber_relationship' => 'self',
                        'effective_date'          => Carbon::now()->startOfYear()->toDateString(),
                        'termination_date'        => Carbon::now()->endOfYear()->toDateString(),
                        'active'                  => true,
                    ]
                );
            }

            $patients[$last] = ['model' => $patient, 'service' => $service];
        }

        foreach ([
            ['name' => 'SOAP', 'slug' => 'soap', 'description' => 'Subjective, Objective, Assessment, Plan — standard SOAP format.'],
            ['name' => 'DAP',  'slug' => 'dap',  'description' => 'Data, Assessment, Plan — concise variant.'],
            ['name' => 'BIRP', 'slug' => 'birp', 'description' => 'Behavior, Intervention, Response, Plan.'],
            ['name' => 'GIRP', 'slug' => 'girp', 'description' => 'Goal, Intervention, Response, Plan.'],
        ] as $tpl) {
            PsrNoteTemplate::firstOrCreate(
                ['slug' => $tpl['slug']],
                array_merge($tpl, ['client_id' => null, 'is_system' => true, 'is_active' => true,
                    'sections' => ['mental_status' => true, 'goals' => true, 'progress' => true, 'plan' => true]])
            );
        }

        $admin = User::where('email', 'admin@demo-bh.local')->first();
        $psrAdms = [];
        $diagnosisOptions = [
            ['F33.1', 'Major depressive disorder, recurrent, moderate'],
            ['F32.9', 'Major depressive disorder, single episode, unspecified'],
            ['F41.1', 'Generalized anxiety disorder'],
        ];
        foreach (['Rivera', 'Gonzalez', 'Diaz'] as $idx => $lname) {
            if (! isset($patients[$lname])) continue;
            $patient   = $patients[$lname]['model'];
            $clinic    = $clinicList[$idx % count($clinicList)];
            [$dx, $dxDesc] = $diagnosisOptions[$idx % count($diagnosisOptions)];

            $adm = PsrAdmission::firstOrCreate(
                ['client_id' => $client->id, 'patient_id' => $patient->id],
                [
                    'clinic_id'                => $clinic->id,
                    'admission_date'           => Carbon::now()->subDays(60 + $idx * 20)->toDateString(),
                    'status'                   => 'admitted',
                    'assigned_therapist_id'    => $employees['david']->id,
                    'referring_provider_id'    => $employees['laura']->id,
                    'referral_date'            => Carbon::now()->subDays(75 + $idx * 20)->toDateString(),
                    'primary_dx_code'          => $dx,
                    'primary_dx_description'   => $dxDesc,
                    'secondary_dx_code'        => 'F43.10',
                    'secondary_dx_description' => 'Post-traumatic stress disorder',
                    'default_shift_pos'        => '11',
                    'risk_score'               => 35 + $idx * 5,
                    'created_by'               => $admin?->id,
                ]
            );
            $psrAdms[] = $adm;

            PsrIntake::firstOrCreate(
                ['psr_admission_id' => $adm->id],
                [
                    'race'                 => 'Hispanic or Latino',
                    'ethnicity'            => 'Hispanic',
                    'preferred_language'   => 'Spanish',
                    'interpreter_needed'   => false,
                    'consent_treatment'    => true,
                    'consent_release_info' => true,
                    'receipt_hipaa'        => true,
                    'receipt_rights'       => true,
                    'consent_telehealth'   => true,
                    'emergency_plan_ack'   => true,
                    'medical_history_checklist' => 'Hypertension; no surgical history.',
                    'allergies'                 => 'Penicillin',
                    'current_medications'       => 'Sertraline 50mg PO daily',
                    'pcp_name'                  => 'Dr. Ana Garcia',
                    'pcp_phone'                 => '(305) 555-7700',
                    'psychiatrist_name'         => 'Dr. Roberto Lopez',
                    'psychiatrist_phone'        => '(305) 555-7800',
                    'safety_contract_agreed'    => true,
                    'safety_plan_details'       => 'Patient agrees to call crisis line if SI emerges; emergency contact is family.',
                    'staff_comments'            => 'Cooperative during intake; provided all consents.',
                    'is_signed'                 => true,
                    'signed_at'                 => Carbon::now()->subDays(60 + $idx * 20),
                    'completed_by'              => $admin?->id,
                ]
            );

            PsrAssessmentBio::firstOrCreate(
                ['psr_admission_id' => $adm->id],
                [
                    'presenting_problem'  => 'Patient presents with persistent low mood, anhedonia, and intermittent panic attacks over the past 6 months.',
                    'history_illness'     => 'First episode at age 24, multiple recurrences, no inpatient history.',
                    'family_history'      => 'Mother with depression; sibling with anxiety disorder.',
                    'medical_history'     => 'Hypertension, controlled with medication.',
                    'medications'         => 'Sertraline 50mg PO daily',
                    'risk_assessment'     => 'Low risk to self and others. No current SI/HI.',
                    'clinical_impression' => 'Patient meets criteria for recurrent MDD with comorbid GAD; benefit expected from PSR program.',
                    'is_signed'           => true,
                    'signed_at'           => Carbon::now()->subDays(58 + $idx * 20),
                    'signed_by'           => $employees['david']->id,
                ]
            );

            $farsScores = ['depression' => 6, 'anxiety' => 5, 'cognitive' => 3, 'thought_process' => 2,
                'interpersonal' => 4, 'family_relationships' => 3, 'work_school' => 5, 'adl' => 2,
                'self_care' => 2, 'danger_self' => 2, 'danger_others' => 1];
            // Checked indicators per domain — counts match the ratings above (rating = checked + 1).
            $farsIndicators = [
                'depression'           => ['Depressed mood', 'Anhedonic', 'Sleep problems', 'Sad', 'Hopeless'],
                'anxiety'              => ['Anxious', 'Tense', 'Fearful', 'Panic'],
                'cognitive'            => ['Poor concentration', 'Short attention'],
                'thought_process'      => ['Ruminative'],
                'interpersonal'        => ['Problems w/friends', 'Difficulty establish/maintain relationships', 'Poor social skills'],
                'family_relationships' => ['Conflict w/relative', 'Difficulty w/partner'],
                'work_school'          => ['Absenteeism', 'Not employed', 'Poor performance', 'Tardiness'],
                'adl'                  => ['Money management problems'],
                'self_care'            => ['Suffers from neglect'],
                'danger_self'          => ['Suicidal ideation'],
            ];
            $fars = PsrFars::firstOrCreate(
                ['psr_admission_id' => $adm->id, 'evaluation_type' => 'admission'],
                array_merge([
                    'evaluation_date'         => Carbon::now()->subDays(58 + $idx * 20),
                    'substance_abuse_history' => false,
                    'indicators_json'         => json_encode($farsIndicators),
                    'mgaf_score'              => 55,
                    'is_signed'               => true,
                    'signed_at'               => Carbon::now()->subDays(58 + $idx * 20),
                    'signed_by'               => $employees['david']->id,
                ], $farsScores)
            );
            $fars->recalculateTotal();
            $fars->save();

            $plan = PsrTreatmentPlan::firstOrCreate(
                ['psr_admission_id' => $adm->id],
                [
                    'start_date'         => Carbon::now()->subDays(55 + $idx * 20)->toDateString(),
                    'end_date'           => Carbon::now()->addDays(125 - $idx * 20)->toDateString(),
                    'strengths'          => ['accepts_feedback', 'motivated_change', 'supportive_family', 'coping_skills', 'positive_therapist'],
                    'weaknesses'         => ['lack_social_skills', 'no_support', 'poor_judgment', 'limited_financial'],
                    'services'           => ['psr_adult', 'individual_therapy', 'medication_mgmt', 'case_management'],
                    'long_term_goal'     => 'Patient will demonstrate sustained remission of depressive symptoms and resume baseline social/occupational functioning.',
                    'discharge_criteria' => 'PHQ-9 below 5 for 60 days; consistent attendance; demonstrates use of coping skills.',
                    'is_signed'          => true,
                    'signed_at'          => Carbon::now()->subDays(54 + $idx * 20),
                    'signed_by'          => $employees['david']->id,
                ]
            );

            // Goals
            $g1 = PsrGoal::firstOrCreate(
                ['psr_treatment_plan_id' => $plan->id, 'goal_code' => 'G1'],
                [
                    'description'        => 'Reduce depressive symptoms to within manageable range.',
                    'problem_statement'  => 'Patient endorses persistent depressed mood interfering with daily activities.',
                    'start_date'         => $plan->start_date,
                    'target_date'        => $plan->end_date,
                    'is_active'          => true,
                ]
            );
            PsrObjective::firstOrCreate(
                ['psr_goal_id' => $g1->id, 'objective_code' => 'O1.1'],
                [
                    'description'              => 'Identify 3 cognitive distortions per group session and reframe at least 1 in real time.',
                    'intervention_type'        => 'CBT',
                    'intervention_description' => 'Therapist will guide cognitive restructuring exercises in group.',
                    'start_date'               => $plan->start_date,
                    'target_date'              => $plan->end_date,
                    'is_active'                => true,
                ]
            );
            PsrObjective::firstOrCreate(
                ['psr_goal_id' => $g1->id, 'objective_code' => 'O1.2'],
                [
                    'description'              => 'Engage in one pleasurable activity per week and report on it.',
                    'intervention_type'        => 'Behavioral activation',
                    'intervention_description' => 'Track weekly activities and discuss in group.',
                    'start_date'               => $plan->start_date,
                    'target_date'              => $plan->end_date,
                    'is_active'                => true,
                ]
            );

            $g2 = PsrGoal::firstOrCreate(
                ['psr_treatment_plan_id' => $plan->id, 'goal_code' => 'G2'],
                [
                    'description'        => 'Improve coping with anxiety triggers.',
                    'problem_statement'  => 'Patient experiences frequent panic episodes triggered by interpersonal conflict.',
                    'start_date'         => $plan->start_date,
                    'target_date'        => $plan->end_date,
                    'is_active'          => true,
                ]
            );
            PsrObjective::firstOrCreate(
                ['psr_goal_id' => $g2->id, 'objective_code' => 'O2.1'],
                [
                    'description'              => 'Demonstrate at least 2 grounding techniques during heightened anxiety.',
                    'intervention_type'        => 'DBT skills',
                    'intervention_description' => 'Teach 5-4-3-2-1 and TIPP skills; practice in vivo.',
                    'start_date'               => $plan->start_date,
                    'target_date'              => $plan->end_date,
                    'is_active'                => true,
                ]
            );

            PsrAuthorization::firstOrCreate(
                ['psr_admission_id' => $adm->id, 'auth_type' => 'initial'],
                [
                    'client_id'            => $client->id,
                    'patient_id'           => $patient->id,
                    'payer_id'             => $medicaid->id,
                    'clinic_id'            => $clinic->id,
                    'auth_number'          => 'AUTH-' . str_pad((string)(100000 + $idx * 13), 6, '0', STR_PAD_LEFT),
                    'status'               => 'approved',
                    'service_code'         => 'H2017',
                    'service_description'  => 'Psychosocial rehabilitation services, per 15 minutes',
                    'modifier_1'           => 'HQ',
                    'place_of_service'     => '11',
                    'units_requested'      => 720,
                    'units_approved'       => 600,
                    'units_used'           => 120,
                    'unit_type'            => 'units',
                    'frequency'            => '5 days/week, 4 hours/day',
                    'requested_start_date' => Carbon::now()->subDays(50 + $idx * 20)->toDateString(),
                    'requested_end_date'   => Carbon::now()->addDays(130 - $idx * 20)->toDateString(),
                    'approved_start_date'  => Carbon::now()->subDays(50 + $idx * 20)->toDateString(),
                    'approved_end_date'    => Carbon::now()->addDays(130 - $idx * 20)->toDateString(),
                    'rendering_provider_employee_id' => $employees['david']->id,
                    'supervising_provider_id'        => $employees['david']->id,
                    'group_npi'              => '1900200300',
                    'rendering_npi'          => $employees['david']->npi,
                    'taxonomy_code'          => '101YM0800X',
                    'member_id'              => 'M' . str_pad((string)$patient->id, 9, '0', STR_PAD_LEFT),
                    'medicaid_id'            => 'FL' . str_pad((string)$patient->id, 8, '0', STR_PAD_LEFT),
                    'payer_external_id'      => $medicaid->edi_payer_id,
                    'plan_type'              => 'Medicaid managed care',
                    'primary_dx_code'        => $dx,
                    'primary_dx_description' => $dxDesc,
                    'clinical_justification' => 'Patient meets medical necessity criteria for PSR per FL Medicaid policy.',
                    'medical_necessity_statement' => 'Without PSR, patient is at risk for further functional decline and possible inpatient admission.',
                    'submission_date'        => Carbon::now()->subDays(52 + $idx * 20)->toDateString(),
                    'decision_date'          => Carbon::now()->subDays(50 + $idx * 20)->toDateString(),
                    'contact_name'           => 'Florida Medicaid Provider Services',
                    'contact_phone'          => '(877) 254-1055',
                    'reference_number'       => 'REF-' . rand(100000, 999999),
                    'units_alert_threshold'  => 80,
                    'expiry_alert_days'      => 14,
                    'notes'                  => 'Initial 6-month authorization. Concurrent review due 30 days before expiration.',
                    'created_by'             => $admin?->id,
                ]
            );
        }

        if (count($psrAdms)) {
            $sessionDate = Carbon::now()->subDays(2);
            $session = PsrGroupSession::firstOrCreate(
                [
                    'client_id'    => $client->id,
                    'clinic_id'    => $miami->id,
                    'session_date' => $sessionDate->toDateString(),
                    'start_time'   => '09:00',
                ],
                [
                    'end_time'         => '13:00',
                    'title'            => 'Coping with anxiety — cognitive reframing',
                    'session_type'     => 'group_therapy',
                    'service_code'     => 'H2017',
                    'modifier'         => 'HQ',
                    'place_of_service' => '11',
                    'lead_therapist_id'=> $employees['david']->id,
                    'co_therapist_id'  => $employees['laura']->id,
                    'max_capacity'     => 10,
                    'break_start_time' => '11:00',
                    'break_end_time'   => '11:30',
                    'break_minutes'    => 30,
                    'activities'       => [
                        ['minute' => 0,   'activity' => 'Check-in and goals review', 'duration' => 30],
                        ['minute' => 30,  'activity' => 'Psychoeducation: cognitive distortions', 'duration' => 60],
                        ['minute' => 90,  'activity' => 'Group exercise: identify and reframe', 'duration' => 60],
                        ['minute' => 180, 'activity' => 'Skills practice: 5-4-3-2-1 grounding', 'duration' => 45],
                        ['minute' => 225, 'activity' => 'Closing reflection and homework', 'duration' => 15],
                    ],
                    'session_summary'  => 'Group engaged well in cognitive reframing exercises. All members contributed examples; therapists facilitated peer feedback.',
                    'status'           => 'completed',
                    'is_signed'        => true,
                    'signed_by'        => $admin?->id,
                    'signed_at'        => $sessionDate->copy()->setTime(14, 0),
                    'created_by'       => $admin?->id,
                ]
            );

            foreach ($psrAdms as $i => $adm) {
                $attendee = PsrGroupSessionAttendee::firstOrCreate(
                    ['psr_group_session_id' => $session->id, 'psr_admission_id' => $adm->id],
                    [
                        'patient_id'          => $adm->patient_id,
                        'attendance_status'   => $i === 2 ? 'late' : 'present',
                        'check_in_time'       => $i === 2 ? '09:25' : '09:00',
                        'check_out_time'      => '13:00',
                        'units'               => 16,
                        'participation_level' => $i === 0 ? 'high' : ($i === 1 ? 'moderate' : 'low'),
                        'individual_notes'    => 'Engaged with peers; demonstrated coping skill use.',
                        'created_by'          => $admin?->id,
                    ]
                );

                // Progress note for this attendee
                $note = PsrProgressNote::firstOrCreate(
                    ['psr_group_session_id' => $session->id, 'patient_id' => $adm->patient_id],
                    [
                        'client_id'         => $client->id,
                        'psr_admission_id'  => $adm->id,
                        'note_date'         => $sessionDate->toDateString(),
                        'start_time'        => '09:00',
                        'end_time'          => '13:00',
                        'units'             => 16,
                        'service_code'      => 'H2017',
                        'modifier'          => 'HQ',
                        'place_of_service'  => '11',
                        'therapist_id'      => $employees['david']->id,
                        'subjective'        => 'Patient reported decreased anxiety this week and improved sleep.',
                        'objective'         => 'Alert, oriented x3. Affect bright. Engaged actively with group.',
                        'intervention'      => 'CBT cognitive restructuring; psychoeducation on distortions.',
                        'response'          => 'Identified 3 distortions in own thinking and reframed with peer support.',
                        'progress'          => 'Progress toward Goal 1 (depressive symptoms) noted. Continues to need support on Goal 2 (anxiety).',
                        'plan'              => 'Continue current PSR plan; review Goal 2 in next session.',
                        'mood'              => 'euthymic',
                        'affect'            => 'congruent',
                        'risk_level'        => 'low',
                        'risk_notes'        => 'No SI/HI. Safety plan reaffirmed.',
                        'goals_addressed'   => [
                            ['goal_id' => null, 'objective_id' => null, 'status' => 'progress', 'note' => 'Demonstrated cognitive reframing.'],
                        ],
                        'participation_level' => $i === 0 ? 'high' : 'moderate',
                        'session_type'      => 'group_therapy',
                        'progress_rating'   => $i === 0 ? 4 : 3,
                        'status'            => 'signed',
                        'is_signed'         => true,
                        'signed_at'         => $sessionDate->copy()->setTime(14, 0),
                        'signed_by'         => $employees['david']->id,
                        'signed_by_user_id' => $admin?->id,
                        'created_by'        => $admin?->id,
                    ]
                );

                // Service log row (one per attendee)
                PsrServiceLog::firstOrCreate(
                    ['psr_group_session_attendee_id' => $attendee->id],
                    [
                        'client_id'             => $client->id,
                        'clinic_id'             => $miami->id,
                        'patient_id'            => $adm->patient_id,
                        'psr_admission_id'      => $adm->id,
                        'service_date'          => $sessionDate->toDateString(),
                        'start_time'            => '09:00',
                        'end_time'              => '13:00',
                        'units'                 => 16,
                        'service_code'          => 'H2017',
                        'modifier'              => 'HQ',
                        'place_of_service'      => '11',
                        'diagnosis_code'        => $adm->primary_dx_code,
                        'diagnosis_description' => $adm->primary_dx_description,
                        'therapist_id'          => $employees['david']->id,
                        'source_type'           => 'group_session',
                        'psr_group_session_id'  => $session->id,
                        'psr_progress_note_id'  => $note->id,
                        'psr_authorization_id'  => PsrAuthorization::where('psr_admission_id', $adm->id)->value('id'),
                        'auth_number'           => PsrAuthorization::where('psr_admission_id', $adm->id)->value('auth_number'),
                        'billing_status'        => $i === 0 ? 'paid' : 'submitted',
                        'has_progress_note'     => true,
                        'note_status'           => 'signed',
                        'is_retroactive'        => false,
                        'paid_amount'           => $i === 0 ? 96.00 : null,
                        'created_by'            => $admin?->id,
                    ]
                );
            }
        }

        // Derive each PSR authorization's used units from its seeded service-log rows.
        foreach ($psrAdms as $adm) {
            PsrAuthorization::where('psr_admission_id', $adm->id)->get()->each->recalcUnitsUsed();
        }

        foreach (['Sanchez', 'Fernandez'] as $lname) {
            if (! isset($patients[$lname])) continue;
            $adm = ItAdmission::firstOrCreate(
                ['client_id' => $client->id, 'patient_id' => $patients[$lname]['model']->id],
                [
                    'therapist_id'   => $employees['laura']->id,
                    'admission_date' => Carbon::now()->subDays(rand(20, 90))->toDateString(),
                    'status'         => 'admitted',
                    'diagnosis_code' => 'F32.1',
                    'authorization_number' => 'AUTH-' . rand(100000, 999999),
                ]
            );

            // Create 2 example sessions
            $createdSessions = [];
            foreach ([14, 7] as $daysAgo) {
                $createdSessions[] = ItSession::firstOrCreate(
                    ['client_id' => $client->id, 'it_admission_id' => $adm->id, 'session_date' => Carbon::now()->subDays($daysAgo)->toDateString()],
                    [
                        'therapist_id'     => $employees['laura']->id,
                        'start_time'       => '15:00',
                        'end_time'         => '15:45',
                        'duration_minutes' => 45,
                        'cpt_code'         => '90834',
                        'place_of_service' => '11',
                        'units'            => 1,
                        'subjective'       => 'Patient reports improved mood over the past week. Sleep quality is better.',
                        'objective'        => 'Alert, oriented, cooperative. Affect congruent with stated mood.',
                        'assessment'       => 'Progress toward treatment goals noted. Continue current plan.',
                        'plan'             => 'Continue weekly sessions. Review coping strategies next session.',
                        'goals_addressed'  => 'Goal 1: Reduce depressive symptoms. Goal 3: Improve sleep hygiene.',
                    ]
                );
            }

            // Treatment plan + goals
            $itPlan = \App\Models\It\TreatmentPlan::firstOrCreate(
                ['it_admission_id' => $adm->id],
                [
                    'start_date'         => Carbon::now()->subDays(60)->toDateString(),
                    'end_date'           => Carbon::now()->addDays(120)->toDateString(),
                    'presenting_problem' => 'Patient presents with persistent depressed mood, anhedonia, and sleep disturbance affecting work and relationships.',
                    'long_term_goal'     => 'Patient will achieve sustained remission of depressive symptoms and resume baseline social and occupational functioning.',
                    'discharge_criteria' => 'PHQ-9 below 5 for 60 consecutive days, demonstrated independent use of three coping skills, consistent attendance ≥80%.',
                    'interventions'      => 'Cognitive-behavioral therapy with behavioral activation, sleep hygiene psychoeducation, motivational interviewing.',
                    'is_signed'          => true,
                    'signed_at'          => Carbon::now()->subDays(58),
                    'signed_by'          => $employees['laura']->id,
                    'signed_by_user_id'  => $admin?->id,
                ]
            );
            if ($itPlan->goals()->doesntExist()) {
                $itGoal = $itPlan->goals()->create([
                    'goal_code'         => 'G1',
                    'description'       => 'Patient will demonstrate reduced depressive symptoms and improved daily functioning.',
                    'problem_statement' => 'Persistent depressed mood interfering with work and relationships.',
                    'start_date'        => Carbon::now()->subDays(60)->toDateString(),
                    'target_date'       => Carbon::now()->addDays(120)->toDateString(),
                    'is_active'         => true,
                ]);
                foreach ([
                    ['G1.1', 'Patient will identify three triggers and verbalize a coping strategy for each within 30 days.', 'CBT'],
                    ['G1.2', 'Patient will report a 30% reduction on PHQ-9 within 90 days.', 'CBT'],
                ] as [$code, $desc, $type]) {
                    $itGoal->objectives()->create([
                        'objective_code'    => $code,
                        'description'       => $desc,
                        'intervention_type' => $type,
                        'start_date'        => Carbon::now()->subDays(60)->toDateString(),
                        'target_date'       => Carbon::now()->addDays(120)->toDateString(),
                        'is_active'         => true,
                    ]);
                }
            }

            // Authorization
            $itAuth = \App\Models\It\Authorization::firstOrCreate(
                ['client_id' => $client->id, 'it_admission_id' => $adm->id],
                [
                    'patient_id'           => $adm->patient_id,
                    'payer_id'             => $aetna->id,
                    'auth_number'          => 'IT-AUTH-' . rand(10000, 99999),
                    'auth_type'            => 'initial',
                    'status'               => 'approved',
                    'requested_start_date' => Carbon::now()->subDays(62)->toDateString(),
                    'requested_end_date'   => Carbon::now()->addDays(120)->toDateString(),
                    'approved_start_date'  => Carbon::now()->subDays(60)->toDateString(),
                    'approved_end_date'    => Carbon::now()->addDays(120)->toDateString(),
                    'approved_units'       => 26,
                    'used_units'           => count($createdSessions),
                    'cpt_codes'            => ['90834', '90837'],
                    'notes'                => 'Outpatient individual therapy, weekly 45-min sessions (90834). Commercial PPO.',
                    'created_by'           => $admin?->id,
                ]
            );

            // Service-log entries (one per seeded session)
            foreach ($createdSessions as $session) {
                \App\Models\It\ServiceLog::firstOrCreate(
                    ['client_id' => $client->id, 'it_admission_id' => $adm->id, 'service_date' => $session->session_date->toDateString()],
                    [
                        'patient_id'             => $adm->patient_id,
                        'it_session_id'          => $session->id,
                        'it_authorization_id'    => $itAuth->id,
                        'auth_number'            => $itAuth->auth_number,
                        'start_time'             => $session->start_time,
                        'end_time'               => $session->end_time,
                        'units'                  => $session->units,
                        'cpt_code'               => $session->cpt_code,
                        'place_of_service'       => $session->place_of_service,
                        'diagnosis_code'         => $adm->diagnosis_code,
                        'diagnosis_description'  => $adm->diagnosis_description,
                        'therapist_id'           => $session->therapist_id,
                        'billing_status'         => 'paid',
                        'paid_amount'            => 95.50,
                        'paid_date'              => Carbon::parse($session->session_date)->addDays(14)->toDateString(),
                        'has_progress_note'      => true,
                        'created_by'             => $admin?->id,
                    ]
                );
            }

            $itAuth->recalcUnitsUsed();
        }

        foreach (['Vargas', 'Mendoza'] as $lname) {
            if (! isset($patients[$lname])) continue;
            $adm = TcmAdmission::firstOrCreate(
                ['client_id' => $client->id, 'patient_id' => $patients[$lname]['model']->id],
                [
                    'case_manager_id' => $employees['miguel']->id,
                    'admission_date'  => Carbon::now()->subDays(rand(30, 150))->toDateString(),
                    'status'          => 'admitted',
                    'diagnosis_code'  => 'F90.0',
                    'service_plan'    => 'Monthly in-person visits, weekly phone check-ins, coordination with school and primary care.',
                ]
            );

            // Create 3 contact notes
            $createdContacts = [];
            foreach ([
                ['days' => 20, 'type' => 'in_person', 'duration' => 60, 'with' => 'Patient + mother', 'summary' => 'Reviewed treatment plan and school accommodations.'],
                ['days' => 10, 'type' => 'phone',     'duration' => 15, 'with' => 'Patient',          'summary' => 'Weekly check-in. No new concerns.'],
                ['days' => 3,  'type' => 'collateral','duration' => 20, 'with' => 'School counselor', 'summary' => 'Coordinated on-campus support.'],
            ] as $c) {
                $createdContacts[] = TcmContact::firstOrCreate(
                    ['client_id' => $client->id, 'tcm_admission_id' => $adm->id, 'contact_at' => Carbon::now()->subDays($c['days'])],
                    [
                        'case_manager_id'  => $employees['miguel']->id,
                        'contact_type'     => $c['type'],
                        'duration_minutes' => $c['duration'],
                        'cpt_code'         => 'T1017',
                        'units'            => (int) ceil($c['duration'] / 15),
                        'place_of_service' => $c['type'] === 'in_person' ? '12' : '11',
                        'with_whom'        => $c['with'],
                        'summary'          => $c['summary'],
                    ]
                );
            }

            // TCM service plan + goals
            $tcmPlan = \App\Models\Tcm\TreatmentPlan::firstOrCreate(
                ['tcm_admission_id' => $adm->id],
                [
                    'start_date'           => Carbon::now()->subDays(120)->toDateString(),
                    'end_date'             => Carbon::now()->addDays(60)->toDateString(),
                    'presenting_problem'   => 'Patient and family need ongoing care coordination across school, primary care, and behavioral health to manage attention-deficit symptoms.',
                    'long_term_goal'       => 'Patient will achieve stable community functioning with ADHD effectively managed through coordinated services and self-advocacy.',
                    'discharge_criteria'   => 'Stable engagement with all natural supports for 60 consecutive days, demonstrated medication adherence ≥90%, no crisis contacts in past 30 days.',
                    'coordination_strategy'=> 'Monthly in-person visits, weekly phone check-ins, quarterly meetings with school IEP team, monthly medication review with PCP.',
                    'is_signed'            => true,
                    'signed_at'            => Carbon::now()->subDays(118),
                    'signed_by'            => $employees['miguel']->id,
                    'signed_by_user_id'    => $admin?->id,
                ]
            );
            if ($tcmPlan->goals()->doesntExist()) {
                $tcmGoal = $tcmPlan->goals()->create([
                    'goal_code'         => 'G1',
                    'description'       => 'Patient will access and engage consistently with community-based services to support ADHD management.',
                    'problem_statement' => 'Inconsistent appointment attendance and gaps in care coordination.',
                    'start_date'        => Carbon::now()->subDays(120)->toDateString(),
                    'target_date'       => Carbon::now()->addDays(60)->toDateString(),
                    'is_active'         => true,
                ]);
                foreach ([
                    ['G1.1', 'Patient will attend 90% of scheduled medical and behavioral-health appointments over the next 90 days.', 'Care coordination'],
                    ['G1.2', 'Case manager will coordinate three referrals (PCP, behavioral health, school IEP) within 30 days.', 'Referral'],
                ] as [$code, $desc, $type]) {
                    $tcmGoal->objectives()->create([
                        'objective_code'    => $code,
                        'description'       => $desc,
                        'intervention_type' => $type,
                        'start_date'        => Carbon::now()->subDays(120)->toDateString(),
                        'target_date'       => Carbon::now()->addDays(60)->toDateString(),
                        'is_active'         => true,
                    ]);
                }
            }

            // TCM authorization
            $tcmAuth = \App\Models\Tcm\Authorization::firstOrCreate(
                ['client_id' => $client->id, 'tcm_admission_id' => $adm->id],
                [
                    'patient_id'           => $adm->patient_id,
                    'payer_id'             => $medicaid->id,
                    'auth_number'          => 'TCM-AUTH-' . rand(10000, 99999),
                    'auth_type'            => 'initial',
                    'status'               => 'approved',
                    'requested_start_date' => Carbon::now()->subDays(122)->toDateString(),
                    'requested_end_date'   => Carbon::now()->addDays(60)->toDateString(),
                    'approved_start_date'  => Carbon::now()->subDays(120)->toDateString(),
                    'approved_end_date'    => Carbon::now()->addDays(60)->toDateString(),
                    'approved_units'       => 80,
                    'used_units'           => collect($createdContacts)->sum('units'),
                    'cpt_codes'            => ['T1017', 'T1016'],
                    'notes'                => 'Targeted case management, 15-min units (T1017). FL Medicaid.',
                    'created_by'           => $admin?->id,
                ]
            );

            // Service-log entries (one per seeded contact)
            foreach ($createdContacts as $contact) {
                \App\Models\Tcm\ServiceLog::firstOrCreate(
                    ['client_id' => $client->id, 'tcm_admission_id' => $adm->id, 'service_date' => $contact->contact_at->toDateString()],
                    [
                        'patient_id'             => $adm->patient_id,
                        'tcm_contact_id'         => $contact->id,
                        'tcm_authorization_id'   => $tcmAuth->id,
                        'auth_number'            => $tcmAuth->auth_number,
                        'units'                  => $contact->units,
                        'cpt_code'               => $contact->cpt_code,
                        'place_of_service'       => $contact->place_of_service,
                        'diagnosis_code'         => $adm->diagnosis_code,
                        'diagnosis_description'  => $adm->diagnosis_description,
                        'case_manager_id'        => $contact->case_manager_id,
                        'billing_status'         => 'paid',
                        'paid_amount'            => 22.50 * $contact->units,
                        'paid_date'              => $contact->contact_at->copy()->addDays(14)->toDateString(),
                        'has_contact_note'       => true,
                        'created_by'             => $admin?->id,
                    ]
                );
            }

            $tcmAuth->recalcUnitsUsed();
        }

        $this->command->info('Demo data seeded: ' . count($patientsData) . ' patients, ' . count($people) . ' employees, ' . count($clinicList) . ' clinics.');
    }
}
