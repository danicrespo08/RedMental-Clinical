<?php

namespace App\Models\Psr;

use App\Models\Hhrr\Clinic;
use App\Models\Concerns\BelongsToClient;
use App\Models\Hhrr\Employee;
use App\Models\Hhrr\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DischargeSummary extends Model
{
    use BelongsToClient;

    protected $table = 'psr_discharge_summaries';

    public const DISCHARGE_TYPES = [
        'planned'        => 'Planned',
        'administrative' => 'Administrative',
        'ama'            => 'Against medical advice',
        'transfer'       => 'Transfer',
        'other'          => 'Other',
    ];

    public const DISCHARGE_REASONS = [
        'goals_met'           => 'Goals met',
        'insurance_exhausted' => 'Insurance exhausted',
        'non_compliance'      => 'Non-compliance',
        'relocation'          => 'Relocation',
        'patient_request'     => 'Patient request',
        'higher_loc'          => 'Stepped up to higher level of care',
        'lower_loc'           => 'Stepped down to lower level of care',
        'other'               => 'Other',
    ];

    public const PROGNOSES = [
        'good'    => 'Good',
        'fair'    => 'Fair',
        'guarded' => 'Guarded',
        'poor'    => 'Poor',
    ];

    public const STATUSES = [
        'draft'     => 'Draft',
        'signed'    => 'Signed',
        'co_signed' => 'Co-signed',
    ];

    protected $fillable = [
        'client_id', 'clinic_id', 'patient_id', 'psr_admission_id',
        'discharge_date', 'discharge_type', 'discharge_reason',
        'admission_date',
        'primary_dx_code', 'primary_dx_description',
        'secondary_dx_code', 'secondary_dx_description',
        'dx_at_discharge_code', 'dx_at_discharge_description',
        'presenting_problems', 'treatment_summary', 'clinical_course',
        'response_to_treatment', 'medications_at_discharge', 'risk_assessment_at_discharge',
        'goals_outcome', 'fars_comparison',
        'total_sessions_attended', 'total_sessions_absent',
        'total_units_billed', 'days_in_program',
        'aftercare_plan', 'aftercare_level', 'aftercare_referrals',
        'follow_up_appointments', 'crisis_plan', 'patient_instructions',
        'therapist_recommendation', 'prognosis',
        'therapist_id', 'is_signed', 'signed_at', 'signed_by_user_id',
        'co_signer_id', 'co_signed_at',
        'status', 'created_by',
    ];

    protected $casts = [
        'discharge_date'   => 'date',
        'admission_date'   => 'date',
        'goals_outcome'    => 'array',
        'fars_comparison'  => 'array',
        'is_signed'        => 'boolean',
        'signed_at'        => 'datetime',
        'co_signed_at'     => 'datetime',
    ];

    public function admission(): BelongsTo  { return $this->belongsTo(Admission::class, 'psr_admission_id'); }
    public function patient(): BelongsTo    { return $this->belongsTo(Patient::class); }
    public function clinic(): BelongsTo     { return $this->belongsTo(Clinic::class); }
    public function therapist(): BelongsTo  { return $this->belongsTo(Employee::class, 'therapist_id'); }
    public function signedByUser(): BelongsTo { return $this->belongsTo(User::class, 'signed_by_user_id'); }
    public function coSigner(): BelongsTo   { return $this->belongsTo(Employee::class, 'co_signer_id'); }
    public function creator(): BelongsTo    { return $this->belongsTo(User::class, 'created_by'); }
}
