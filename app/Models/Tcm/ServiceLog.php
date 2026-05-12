<?php

namespace App\Models\Tcm;

use App\Models\Concerns\BelongsToClient;
use App\Models\Hhrr\Employee;
use App\Models\Hhrr\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceLog extends Model
{
    use BelongsToClient;

    protected $table = 'tcm_service_log';

    public const BILLING_STATUSES = [
        'unbilled'  => 'Unbilled',
        'submitted' => 'Submitted',
        'paid'      => 'Paid',
        'denied'    => 'Denied',
        'void'      => 'Void',
    ];

    protected $fillable = [
        'client_id', 'patient_id', 'tcm_admission_id', 'tcm_contact_id',
        'service_date', 'start_time', 'end_time', 'units',
        'cpt_code', 'modifier', 'place_of_service',
        'diagnosis_code', 'diagnosis_description',
        'case_manager_id',
        'tcm_authorization_id', 'auth_number',
        'billing_status', 'claim_number', 'billed_date',
        'paid_date', 'paid_amount', 'denial_reason',
        'has_contact_note', 'notes', 'created_by',
    ];

    protected $casts = [
        'service_date'     => 'date',
        'billed_date'      => 'date',
        'paid_date'        => 'date',
        'paid_amount'      => 'decimal:2',
        'has_contact_note' => 'boolean',
    ];

    public function admission(): BelongsTo     { return $this->belongsTo(Admission::class, 'tcm_admission_id'); }
    public function patient(): BelongsTo       { return $this->belongsTo(Patient::class); }
    public function caseManager(): BelongsTo   { return $this->belongsTo(Employee::class, 'case_manager_id'); }
    public function contact(): BelongsTo       { return $this->belongsTo(Contact::class, 'tcm_contact_id'); }
    public function authorization(): BelongsTo { return $this->belongsTo(Authorization::class, 'tcm_authorization_id'); }
    public function creator(): BelongsTo       { return $this->belongsTo(User::class, 'created_by'); }
}
