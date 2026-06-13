<?php

namespace App\Models\Tcm;

use App\Models\Concerns\BelongsToClient;
use App\Models\Concerns\LocksWhenDischarged;
use App\Models\Hhrr\Employee;
use App\Models\Hhrr\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressNote extends Model
{
    use LocksWhenDischarged;
    use BelongsToClient;

    protected $table = 'tcm_progress_notes';

    public const STATUSES = [
        'draft'    => 'Draft',
        'signed'   => 'Signed',
        'addendum' => 'Addendum',
    ];

    public const NOTE_TYPES = [
        'coordination' => 'Care coordination',
        'assessment'   => 'Assessment / re-assessment',
        'collateral'   => 'Collateral contact',
        'supervision'  => 'Supervision',
        'crisis'       => 'Crisis intervention',
        'other'        => 'Other',
    ];

    public const RISK_LEVELS = [
        'none'     => 'None',
        'low'      => 'Low',
        'moderate' => 'Moderate',
        'high'     => 'High',
    ];

    protected $fillable = [
        'client_id', 'tcm_admission_id', 'patient_id',
        'note_date', 'case_manager_id', 'note_type',
        'summary', 'interventions', 'coordination', 'progress', 'plan',
        'risk_level', 'risk_notes', 'goals_addressed',
        'status', 'is_signed', 'signed_at', 'signed_by', 'signed_by_user_id',
        'addendum_text', 'addendum_date', 'addendum_by',
        'created_by',
    ];

    protected $casts = [
        'note_date'     => 'date',
        'is_signed'     => 'boolean',
        'signed_at'     => 'datetime',
        'addendum_date' => 'datetime',
    ];

    public function admission(): BelongsTo     { return $this->belongsTo(Admission::class, 'tcm_admission_id'); }
    public function patient(): BelongsTo       { return $this->belongsTo(Patient::class); }
    public function caseManager(): BelongsTo   { return $this->belongsTo(Employee::class, 'case_manager_id'); }
    public function signedByEmployee(): BelongsTo { return $this->belongsTo(Employee::class, 'signed_by'); }
    public function signedByUser(): BelongsTo  { return $this->belongsTo(User::class, 'signed_by_user_id'); }
    public function addendumBy(): BelongsTo    { return $this->belongsTo(User::class, 'addendum_by'); }
    public function creator(): BelongsTo       { return $this->belongsTo(User::class, 'created_by'); }
}
