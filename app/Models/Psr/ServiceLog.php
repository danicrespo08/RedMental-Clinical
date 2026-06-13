<?php

namespace App\Models\Psr;

use App\Models\Concerns\FrozenBySuperbillLock;
use App\Models\Concerns\LocksWhenDischarged;
use App\Models\Hhrr\Clinic;
use App\Models\Concerns\BelongsToClient;
use App\Models\Hhrr\Employee;
use App\Models\Hhrr\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceLog extends Model
{
    use FrozenBySuperbillLock;
    use LocksWhenDischarged;
    use BelongsToClient;

    protected $table = 'psr_service_log';

    public const SOURCE_TYPES = [
        'group_session' => 'Group session',
        'individual'    => 'Individual',
        'assessment'    => 'Assessment',
        'retroactive'   => 'Retroactive',
    ];

    public const BILLING_STATUSES = [
        'unbilled'  => 'Unbilled',
        'submitted' => 'Submitted',
        'paid'      => 'Paid',
        'denied'    => 'Denied',
        'void'      => 'Void',
    ];

    protected $fillable = [
        'client_id', 'clinic_id', 'patient_id', 'psr_admission_id',
        'service_date', 'start_time', 'end_time', 'units',
        'service_code', 'modifier', 'place_of_service',
        'diagnosis_code', 'diagnosis_description',
        'therapist_id',
        'source_type', 'psr_group_session_id',
        'psr_group_session_attendee_id', 'psr_progress_note_id',
        'psr_authorization_id', 'auth_number',
        'billing_status', 'claim_number', 'billed_date',
        'paid_date', 'paid_amount', 'denial_reason',
        'has_progress_note', 'note_status', 'is_retroactive',
        'notes', 'created_by',
    ];

    protected $casts = [
        'service_date'      => 'date',
        'billed_date'       => 'date',
        'paid_date'         => 'date',
        'paid_amount'       => 'decimal:2',
        'has_progress_note' => 'boolean',
        'is_retroactive'    => 'boolean',
    ];

    public function admission(): BelongsTo     { return $this->belongsTo(Admission::class, 'psr_admission_id'); }
    public function patient(): BelongsTo       { return $this->belongsTo(Patient::class); }
    public function therapist(): BelongsTo     { return $this->belongsTo(Employee::class, 'therapist_id'); }
    public function clinic(): BelongsTo        { return $this->belongsTo(Clinic::class); }
    public function authorization(): BelongsTo { return $this->belongsTo(Authorization::class, 'psr_authorization_id'); }
    public function progressNote(): BelongsTo  { return $this->belongsTo(ProgressNote::class, 'psr_progress_note_id'); }
    public function groupSession(): BelongsTo  { return $this->belongsTo(GroupSession::class, 'psr_group_session_id'); }
    public function creator(): BelongsTo       { return $this->belongsTo(User::class, 'created_by'); }
}
