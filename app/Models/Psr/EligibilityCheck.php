<?php

namespace App\Models\Psr;

use App\Models\Concerns\LocksWhenDischarged;
use App\Models\Concerns\BelongsToClient;
use App\Models\Hhrr\Patient;
use App\Models\Hhrr\Payer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EligibilityCheck extends Model
{
    use LocksWhenDischarged;
    use BelongsToClient;

    protected $table = 'psr_eligibility_checks';

    public const RESULTS = [
        'active'      => 'Active',
        'terminated'  => 'Terminated',
        'pending'     => 'Pending',
        'no_coverage' => 'No coverage',
        'error'       => 'Error',
    ];

    protected $fillable = [
        'client_id', 'patient_id', 'psr_admission_id', 'payer_id',
        'check_date', 'member_id', 'result',
        'coverage_start', 'coverage_end',
        'plan_name', 'plan_type', 'raw_response',
        'notes', 'checked_by',
    ];

    protected $casts = [
        'check_date'     => 'date',
        'coverage_start' => 'date',
        'coverage_end'   => 'date',
        'raw_response'   => 'array',
    ];

    public function patient(): BelongsTo   { return $this->belongsTo(Patient::class); }
    public function admission(): BelongsTo { return $this->belongsTo(Admission::class, 'psr_admission_id'); }
    public function payer(): BelongsTo     { return $this->belongsTo(Payer::class); }
    public function checkedBy(): BelongsTo { return $this->belongsTo(User::class, 'checked_by'); }
}
