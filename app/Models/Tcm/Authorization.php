<?php

namespace App\Models\Tcm;

use App\Models\Concerns\BelongsToClient;
use App\Models\Hhrr\Patient;
use App\Models\Hhrr\Payer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Authorization extends Model
{
    use BelongsToClient;

    protected $table = 'tcm_authorizations';

    public const TYPES = [
        'initial'        => 'Initial',
        'concurrent'     => 'Concurrent',
        'retrospective'  => 'Retrospective',
    ];

    public const STATUSES = [
        'pending'   => 'Pending',
        'submitted' => 'Submitted',
        'approved'  => 'Approved',
        'denied'    => 'Denied',
        'expired'   => 'Expired',
    ];

    protected $fillable = [
        'client_id', 'tcm_admission_id', 'patient_id', 'payer_id',
        'auth_number', 'auth_type', 'status',
        'requested_start_date', 'requested_end_date',
        'approved_start_date',  'approved_end_date',
        'approved_units', 'used_units',
        'cpt_codes', 'denial_reason', 'notes',
        'created_by',
    ];

    protected $casts = [
        'requested_start_date' => 'date',
        'requested_end_date'   => 'date',
        'approved_start_date'  => 'date',
        'approved_end_date'    => 'date',
        'cpt_codes'            => 'array',
    ];

    public function admission(): BelongsTo { return $this->belongsTo(Admission::class, 'tcm_admission_id'); }
    public function patient(): BelongsTo   { return $this->belongsTo(Patient::class); }
    public function payer(): BelongsTo     { return $this->belongsTo(Payer::class); }
    public function creator(): BelongsTo   { return $this->belongsTo(User::class, 'created_by'); }

    public function getRemainingUnitsAttribute(): int
    {
        return max(0, (int) ($this->approved_units ?? 0) - (int) ($this->used_units ?? 0));
    }
}
