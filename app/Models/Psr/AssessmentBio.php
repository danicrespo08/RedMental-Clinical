<?php

namespace App\Models\Psr;

use App\Models\Hhrr\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentBio extends Model
{
    protected $table = 'psr_assessments_bio';

    protected $fillable = [
        'psr_admission_id',
        'presenting_problem', 'history_illness', 'family_history',
        'medical_history', 'medications', 'risk_assessment', 'clinical_impression',
        'is_signed', 'signed_at', 'signed_by', 'signed_by_user_id',
    ];

    protected $casts = [
        'is_signed' => 'boolean',
        'signed_at' => 'datetime',
    ];

    public function admission(): BelongsTo      { return $this->belongsTo(Admission::class, 'psr_admission_id'); }
    public function signedByEmployee(): BelongsTo { return $this->belongsTo(Employee::class, 'signed_by'); }
    public function signedByUser(): BelongsTo   { return $this->belongsTo(User::class, 'signed_by_user_id'); }
}
