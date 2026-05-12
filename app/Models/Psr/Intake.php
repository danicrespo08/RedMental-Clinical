<?php

namespace App\Models\Psr;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Intake extends Model
{
    protected $table = 'psr_intakes';

    protected $fillable = [
        'psr_admission_id',
        'race', 'ethnicity', 'preferred_language', 'interpreter_needed',
        'legal_guardian_name', 'legal_guardian_relationship', 'legal_guardian_phone',
        'consent_treatment', 'consent_release_info', 'receipt_hipaa',
        'receipt_rights', 'consent_telehealth', 'emergency_plan_ack',
        'medical_history_checklist', 'allergies', 'current_medications',
        'pcp_name', 'pcp_phone', 'psychiatrist_name', 'psychiatrist_phone',
        'safety_contract_agreed', 'safety_plan_details',
        'staff_comments', 'form_data',
        'is_signed', 'signed_at', 'patient_signature_data', 'completed_by',
    ];

    protected $casts = [
        'interpreter_needed'      => 'boolean',
        'consent_treatment'       => 'boolean',
        'consent_release_info'    => 'boolean',
        'receipt_hipaa'           => 'boolean',
        'receipt_rights'          => 'boolean',
        'consent_telehealth'      => 'boolean',
        'emergency_plan_ack'      => 'boolean',
        'safety_contract_agreed'  => 'boolean',
        'is_signed'               => 'boolean',
        'signed_at'               => 'datetime',
        'form_data'               => 'array',
    ];

    public function admission(): BelongsTo  { return $this->belongsTo(Admission::class, 'psr_admission_id'); }
    public function completedBy(): BelongsTo { return $this->belongsTo(User::class, 'completed_by'); }
}
