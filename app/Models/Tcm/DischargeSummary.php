<?php

namespace App\Models\Tcm;

use App\Models\Concerns\BelongsToClient;
use App\Models\Hhrr\Employee;
use App\Models\Hhrr\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DischargeSummary extends Model
{
    use BelongsToClient;

    protected $table = 'tcm_discharge_summaries';

    public const DISCHARGE_TYPES = [
        'planned'        => 'Planned',
        'administrative' => 'Administrative',
        'ama'            => 'Against medical advice',
        'transfer'       => 'Transfer',
        'other'          => 'Other',
    ];

    public const DISCHARGE_REASONS = [
        'goals_met'         => 'Goals met',
        'no_show'           => 'No-show pattern',
        'relocation'        => 'Relocation',
        'higher_level'      => 'Stepped up to higher level of care',
        'lower_level'       => 'Stepped down to lower level of care',
        'patient_choice'    => 'Patient choice',
        'admin_termination' => 'Administrative termination',
    ];

    public const PROGNOSES = [
        'good'    => 'Good',
        'fair'    => 'Fair',
        'guarded' => 'Guarded',
        'poor'    => 'Poor',
    ];

    public const STATUSES = [
        'draft'  => 'Draft',
        'signed' => 'Signed',
    ];

    protected $fillable = [
        'client_id', 'patient_id', 'tcm_admission_id',
        'discharge_date', 'discharge_type', 'discharge_reason',
        'admission_date',
        'primary_dx_code', 'primary_dx_description',
        'dx_at_discharge_code', 'dx_at_discharge_description',
        'presenting_problems', 'case_management_summary', 'coordination_outcomes',
        'response_to_services', 'barriers_identified', 'risk_assessment_at_discharge',
        'goals_outcome',
        'total_contacts', 'total_units_billed', 'days_in_program',
        'aftercare_plan', 'aftercare_level', 'aftercare_referrals',
        'community_resources', 'crisis_plan', 'patient_instructions',
        'case_manager_recommendation', 'prognosis',
        'case_manager_id', 'is_signed', 'signed_at', 'signed_by_user_id',
        'status', 'created_by',
    ];

    protected $casts = [
        'discharge_date' => 'date',
        'admission_date' => 'date',
        'goals_outcome'  => 'array',
        'is_signed'      => 'boolean',
        'signed_at'      => 'datetime',
    ];

    public function admission(): BelongsTo    { return $this->belongsTo(Admission::class, 'tcm_admission_id'); }
    public function patient(): BelongsTo      { return $this->belongsTo(Patient::class); }
    public function caseManager(): BelongsTo  { return $this->belongsTo(Employee::class, 'case_manager_id'); }
    public function signedByUser(): BelongsTo { return $this->belongsTo(User::class, 'signed_by_user_id'); }
}
