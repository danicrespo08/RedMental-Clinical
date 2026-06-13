<?php

namespace App\Models\Psr;

use App\Models\Concerns\LocksWhenDischarged;
use App\Models\Hhrr\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fars extends Model
{
    use LocksWhenDischarged;

    protected $table = 'psr_fars';

    public const DOMAINS = [
        'depression', 'security', 'hyperaffect', 'anxiety', 'cognitive',
        'thought_process', 'traumatic_stress', 'medical_physical',
        'interpersonal', 'family_relationships', 'family_environment',
        'substance_use', 'work_school', 'socio_legal',
        'danger_others', 'danger_self', 'adl', 'self_care',
    ];

    public const EVALUATION_TYPES = [
        'admission' => 'Admission',
        'periodic'  => 'Periodic',
        'discharge' => 'Discharge',
    ];

    protected $fillable = [
        'psr_admission_id', 'evaluation_type', 'evaluation_date',
        'depression', 'security', 'hyperaffect', 'anxiety', 'cognitive',
        'thought_process', 'traumatic_stress', 'medical_physical',
        'interpersonal', 'family_relationships', 'family_environment',
        'substance_use', 'work_school', 'socio_legal',
        'danger_others', 'danger_self', 'adl', 'self_care',
        'indicators_json', 'substance_abuse_history',
        'total_score', 'mgaf_score',
        'is_signed', 'signed_at', 'signed_by', 'signed_by_user_id',
    ];

    protected $casts = [
        'evaluation_date'         => 'datetime',
        'substance_abuse_history' => 'boolean',
        'is_signed'               => 'boolean',
        'signed_at'               => 'datetime',
    ];

    public function admission(): BelongsTo      { return $this->belongsTo(Admission::class, 'psr_admission_id'); }
    public function signedByEmployee(): BelongsTo { return $this->belongsTo(Employee::class, 'signed_by'); }
    public function signedByUser(): BelongsTo   { return $this->belongsTo(User::class, 'signed_by_user_id'); }

    /** Recompute the total of all 18 domain scores. */
    public function recalculateTotal(): void
    {
        $this->total_score = collect(self::DOMAINS)->sum(fn ($d) => (int) $this->{$d});
    }
}
