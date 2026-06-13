<?php

namespace App\Models\Tcm;

use App\Models\Concerns\BelongsToClient;
use App\Models\Hhrr\Employee;
use App\Models\Hhrr\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admission extends Model
{
    use BelongsToClient;

    protected $table = 'tcm_admissions';

    protected $fillable = [
        'client_id', 'patient_id', 'case_manager_id',
        'admission_date', 'discharge_date', 'status',
        'diagnosis_code', 'diagnosis_description',
        'authorization_number', 'service_plan', 'notes',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'discharge_date' => 'date',
    ];

    public function patient(): BelongsTo     { return $this->belongsTo(Patient::class); }
    public function caseManager(): BelongsTo { return $this->belongsTo(Employee::class, 'case_manager_id'); }

    public function contacts(): HasMany         { return $this->hasMany(Contact::class, 'tcm_admission_id'); }
    public function treatmentPlans(): HasMany   { return $this->hasMany(TreatmentPlan::class, 'tcm_admission_id'); }
    public function authorizations(): HasMany   { return $this->hasMany(Authorization::class, 'tcm_admission_id'); }
    public function serviceLogs(): HasMany      { return $this->hasMany(ServiceLog::class, 'tcm_admission_id'); }
    public function dischargeSummary()          { return $this->hasOne(DischargeSummary::class, 'tcm_admission_id'); }

    /** A signed service plan is required before care contacts can be logged. */
    public function signedTreatmentPlan()
    {
        return $this->treatmentPlans()->where('is_signed', true)->latest('id')->first();
    }

    public function hasSignedTreatmentPlan(): bool
    {
        return $this->treatmentPlans()->where('is_signed', true)->exists();
    }

    public function latestTreatmentPlan()
    {
        return $this->hasOne(TreatmentPlan::class, 'tcm_admission_id')->latestOfMany('start_date');
    }

    public const STATUSES = [
        'admitted'   => 'Admitted',
        'on_hold'    => 'On hold',
        'discharged' => 'Discharged',
    ];
}
