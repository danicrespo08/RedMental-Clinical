<?php

namespace App\Models\Psr;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosisHistory extends Model
{
    protected $table = 'psr_diagnosis_history';

    protected $fillable = [
        'psr_admission_id', 'effective_date',
        'primary_dx_code', 'primary_dx_description',
        'secondary_dx_code', 'secondary_dx_description',
        'notes', 'recorded_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function admission(): BelongsTo  { return $this->belongsTo(Admission::class, 'psr_admission_id'); }
    public function recordedBy(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}
