<?php

namespace App\Models\It;

use App\Models\Concerns\BelongsToClient;
use App\Models\Hhrr\Employee;
use App\Models\Hhrr\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admission extends Model
{
    use BelongsToClient;

    protected $table = 'it_admissions';

    protected $fillable = [
        'client_id', 'patient_id', 'therapist_id',
        'admission_date', 'discharge_date', 'status',
        'diagnosis_code', 'diagnosis_description',
        'authorization_number', 'notes',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'discharge_date' => 'date',
    ];

    public function patient(): BelongsTo   { return $this->belongsTo(Patient::class); }
    public function therapist(): BelongsTo { return $this->belongsTo(Employee::class, 'therapist_id'); }

    public function sessions(): HasMany         { return $this->hasMany(Session::class, 'it_admission_id'); }
    public function treatmentPlans(): HasMany   { return $this->hasMany(TreatmentPlan::class, 'it_admission_id'); }
    public function authorizations(): HasMany   { return $this->hasMany(Authorization::class, 'it_admission_id'); }
    public function serviceLogs(): HasMany      { return $this->hasMany(ServiceLog::class, 'it_admission_id'); }
    public function dischargeSummary()          { return $this->hasOne(DischargeSummary::class, 'it_admission_id'); }

    public function latestTreatmentPlan()
    {
        return $this->hasOne(TreatmentPlan::class, 'it_admission_id')->latestOfMany('start_date');
    }

    public const STATUSES = [
        'admitted'   => 'Admitted',
        'on_hold'    => 'On hold',
        'discharged' => 'Discharged',
    ];
}
