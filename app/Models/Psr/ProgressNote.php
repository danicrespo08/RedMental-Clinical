<?php

namespace App\Models\Psr;

use App\Models\Concerns\BelongsToClient;
use App\Models\Hhrr\Employee;
use App\Models\Hhrr\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressNote extends Model
{
    use BelongsToClient;

    protected $table = 'psr_progress_notes';

    public const STATUSES = [
        'draft'    => 'Draft',
        'signed'   => 'Signed',
        'addendum' => 'Addendum',
    ];

    public const RISK_LEVELS = [
        'none'     => 'None',
        'low'      => 'Low',
        'moderate' => 'Moderate',
        'high'     => 'High',
    ];

    protected $fillable = [
        'client_id', 'psr_group_session_id', 'psr_admission_id',
        'patient_id', 'note_template_id',
        'note_date', 'start_time', 'end_time', 'units',
        'service_code', 'modifier', 'place_of_service',
        'therapist_id',
        'subjective', 'objective', 'intervention', 'response', 'progress', 'plan',
        'mood', 'affect', 'risk_level', 'risk_notes',
        'goals_addressed', 'participation_level', 'session_type',
        'progress_rating', 'extra_data',
        'status', 'is_signed', 'signed_at', 'signed_by', 'signed_by_user_id',
        'co_signer_id', 'co_signed_at',
        'addendum_text', 'addendum_date', 'addendum_by',
        'created_by',
    ];

    protected $casts = [
        'note_date'       => 'date',
        'is_signed'       => 'boolean',
        'signed_at'       => 'datetime',
        'co_signed_at'    => 'datetime',
        'addendum_date'   => 'datetime',
        'goals_addressed' => 'array',
        'extra_data'      => 'array',
    ];

    public function admission(): BelongsTo     { return $this->belongsTo(Admission::class, 'psr_admission_id'); }
    public function patient(): BelongsTo       { return $this->belongsTo(Patient::class); }
    public function therapist(): BelongsTo     { return $this->belongsTo(Employee::class, 'therapist_id'); }
    public function groupSession(): BelongsTo  { return $this->belongsTo(GroupSession::class, 'psr_group_session_id'); }
    public function template(): BelongsTo      { return $this->belongsTo(NoteTemplate::class, 'note_template_id'); }
    public function signedByEmployee(): BelongsTo { return $this->belongsTo(Employee::class, 'signed_by'); }
    public function signedByUser(): BelongsTo  { return $this->belongsTo(User::class, 'signed_by_user_id'); }
    public function coSigner(): BelongsTo      { return $this->belongsTo(Employee::class, 'co_signer_id'); }
    public function addendumBy(): BelongsTo    { return $this->belongsTo(User::class, 'addendum_by'); }
    public function creator(): BelongsTo       { return $this->belongsTo(User::class, 'created_by'); }
}
