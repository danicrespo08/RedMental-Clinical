<?php

namespace App\Models\Psr;

use App\Models\Hhrr\Clinic;
use App\Models\Concerns\BelongsToClient;
use App\Models\Hhrr\Employee;
use App\Models\Hhrr\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Admission extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $table = 'psr_admissions';

    public const STATUSES = [
        'pending_intake'  => 'Pending intake',
        'intake_complete' => 'Intake complete',
        'admitted'        => 'Admitted',
        'on_hold'         => 'On hold',
        'discharged'      => 'Discharged',
    ];

    protected $fillable = [
        'client_id', 'clinic_id', 'patient_id',
        'admission_date', 'discharge_date', 'status',
        'assigned_therapist_id', 'referring_provider_id', 'referral_date',
        'primary_dx_code', 'primary_dx_description',
        'secondary_dx_code', 'secondary_dx_description',
        'default_shift_pos', 'risk_score',
        'created_by',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'discharge_date' => 'date',
        'referral_date'  => 'date',
        'risk_score'     => 'integer',
    ];

    public function patient(): BelongsTo            { return $this->belongsTo(Patient::class); }
    public function clinic(): BelongsTo             { return $this->belongsTo(Clinic::class); }
    public function assignedTherapist(): BelongsTo  { return $this->belongsTo(Employee::class, 'assigned_therapist_id'); }
    public function referringProvider(): BelongsTo  { return $this->belongsTo(Employee::class, 'referring_provider_id'); }
    public function createdBy(): BelongsTo          { return $this->belongsTo(User::class, 'created_by'); }

    public function intake(): HasOne                { return $this->hasOne(Intake::class, 'psr_admission_id'); }
    public function bioAssessment(): HasOne         { return $this->hasOne(AssessmentBio::class, 'psr_admission_id'); }
    public function dischargeSummary(): HasOne      { return $this->hasOne(DischargeSummary::class, 'psr_admission_id'); }

    public function documents(): HasMany            { return $this->hasMany(AdmissionDocument::class, 'psr_admission_id'); }
    public function diagnosisHistory(): HasMany     { return $this->hasMany(DiagnosisHistory::class, 'psr_admission_id'); }
    public function farsAssessments(): HasMany      { return $this->hasMany(Fars::class, 'psr_admission_id'); }
    public function treatmentPlans(): HasMany       { return $this->hasMany(TreatmentPlan::class, 'psr_admission_id'); }
    public function authorizations(): HasMany       { return $this->hasMany(Authorization::class, 'psr_admission_id'); }
    public function eligibilityChecks(): HasMany    { return $this->hasMany(EligibilityCheck::class, 'psr_admission_id'); }
    public function progressNotes(): HasMany        { return $this->hasMany(ProgressNote::class, 'psr_admission_id'); }
    public function serviceLogs(): HasMany          { return $this->hasMany(ServiceLog::class, 'psr_admission_id'); }
    public function groupSessionAttendances(): HasMany { return $this->hasMany(GroupSessionAttendee::class, 'psr_admission_id'); }

    public function getDaysInProgramAttribute(): ?int
    {
        if (!$this->admission_date) return null;
        $end = $this->discharge_date ?: now();
        return (int) $this->admission_date->diffInDays($end, false);
    }

    /** Human-readable status label, used by status badges. */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }
}
