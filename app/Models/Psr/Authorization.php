<?php

namespace App\Models\Psr;

use App\Models\Hhrr\Clinic;
use App\Models\Concerns\BelongsToClient;
use App\Models\Hhrr\Employee;
use App\Models\Hhrr\Patient;
use App\Models\Hhrr\Payer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Authorization extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $table = 'psr_authorizations';

    public const AUTH_TYPES = [
        'initial'     => 'Initial',
        'concurrent'  => 'Concurrent',
        'retroactive' => 'Retroactive',
    ];

    public const STATUSES = [
        'pending'        => 'Pending',
        'submitted'      => 'Submitted',
        'approved'       => 'Approved',
        'denied'         => 'Denied',
        'expired'        => 'Expired',
        'pending_review' => 'Pending review',
    ];

    public const UNIT_TYPES = [
        'units'  => 'Units',
        'hours'  => 'Hours',
        'days'   => 'Days',
        'visits' => 'Visits',
    ];

    protected $fillable = [
        'client_id', 'psr_admission_id', 'patient_id', 'payer_id', 'clinic_id',
        'auth_number', 'auth_type', 'status',
        'service_code', 'service_description',
        'modifier_1', 'modifier_2', 'modifier_3', 'modifier_4',
        'place_of_service', 'revenue_code',
        'units_requested', 'units_approved', 'units_used',
        'unit_type', 'frequency',
        'requested_start_date', 'requested_end_date',
        'approved_start_date', 'approved_end_date',
        'rendering_provider_employee_id', 'supervising_provider_id',
        'group_npi', 'rendering_npi', 'taxonomy_code',
        'member_id', 'medicaid_id', 'payer_external_id', 'plan_type',
        'primary_dx_code', 'primary_dx_description',
        'secondary_dx_code', 'secondary_dx_description',
        'clinical_justification', 'medical_necessity_statement',
        'submission_date', 'decision_date', 'denial_reason', 'appeal_notes',
        'contact_name', 'contact_phone', 'reference_number',
        'units_alert_threshold', 'expiry_alert_days',
        'docusign_envelope_id', 'docusign_status',
        'docusign_sent_at', 'docusign_completed_at',
        'notes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'requested_start_date'  => 'date',
        'requested_end_date'    => 'date',
        'approved_start_date'   => 'date',
        'approved_end_date'     => 'date',
        'submission_date'       => 'date',
        'decision_date'         => 'date',
        'docusign_sent_at'      => 'datetime',
        'docusign_completed_at' => 'datetime',
    ];

    public function admission(): BelongsTo  { return $this->belongsTo(Admission::class, 'psr_admission_id'); }
    public function patient(): BelongsTo    { return $this->belongsTo(Patient::class); }
    public function payer(): BelongsTo      { return $this->belongsTo(Payer::class); }
    public function clinic(): BelongsTo     { return $this->belongsTo(Clinic::class); }
    public function renderingProvider(): BelongsTo { return $this->belongsTo(Employee::class, 'rendering_provider_employee_id'); }
    public function supervisingProvider(): BelongsTo { return $this->belongsTo(Employee::class, 'supervising_provider_id'); }
    public function createdBy(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo  { return $this->belongsTo(User::class, 'updated_by'); }
    public function serviceLogs(): HasMany  { return $this->hasMany(ServiceLog::class, 'psr_authorization_id'); }

    public function getUnitsRemainingAttribute(): ?int
    {
        return $this->units_approved !== null ? max(0, (int) $this->units_approved - (int) $this->units_used) : null;
    }

    public function getUnitsUsedPercentAttribute(): ?float
    {
        return ($this->units_approved && $this->units_approved > 0)
            ? round(((int) $this->units_used / (int) $this->units_approved) * 100, 1)
            : null;
    }

    public function getDaysToExpiryAttribute(): ?int
    {
        return $this->approved_end_date
            ? (int) now()->startOfDay()->diffInDays($this->approved_end_date->startOfDay(), false)
            : null;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->approved_end_date && $this->approved_end_date->isPast();
    }
}
