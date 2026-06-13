<?php

namespace App\Models\It;

use App\Models\Concerns\FrozenBySuperbillLock;
use App\Models\Concerns\LocksWhenDischarged;
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

    protected $table = 'it_service_log';

    public const BILLING_STATUSES = [
        'unbilled'  => 'Unbilled',
        'submitted' => 'Submitted',
        'paid'      => 'Paid',
        'denied'    => 'Denied',
        'void'      => 'Void',
    ];

    protected $fillable = [
        'client_id', 'patient_id', 'it_admission_id', 'it_session_id',
        'service_date', 'start_time', 'end_time', 'units',
        'cpt_code', 'modifier', 'place_of_service',
        'diagnosis_code', 'diagnosis_description',
        'therapist_id',
        'it_authorization_id', 'auth_number',
        'billing_status', 'claim_number', 'billed_date',
        'paid_date', 'paid_amount', 'denial_reason',
        'has_progress_note', 'notes', 'created_by',
    ];

    protected $casts = [
        'service_date'      => 'date',
        'billed_date'       => 'date',
        'paid_date'         => 'date',
        'paid_amount'       => 'decimal:2',
        'has_progress_note' => 'boolean',
    ];

    public function admission(): BelongsTo     { return $this->belongsTo(Admission::class, 'it_admission_id'); }
    public function patient(): BelongsTo       { return $this->belongsTo(Patient::class); }
    public function therapist(): BelongsTo     { return $this->belongsTo(Employee::class, 'therapist_id'); }
    public function session(): BelongsTo       { return $this->belongsTo(Session::class, 'it_session_id'); }
    public function authorization(): BelongsTo { return $this->belongsTo(Authorization::class, 'it_authorization_id'); }
    public function creator(): BelongsTo       { return $this->belongsTo(User::class, 'created_by'); }
}
