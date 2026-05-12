<?php

namespace App\Models\It;

use App\Models\Hhrr\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class TreatmentPlan extends Model
{
    protected $table = 'it_treatment_plans';

    protected $fillable = [
        'it_admission_id',
        'start_date', 'end_date',
        'presenting_problem', 'long_term_goal',
        'discharge_criteria', 'interventions',
        'is_signed', 'signed_at', 'signed_by', 'signed_by_user_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_signed'  => 'boolean',
        'signed_at'  => 'datetime',
    ];

    public function admission(): BelongsTo { return $this->belongsTo(Admission::class, 'it_admission_id'); }
    public function goals(): HasMany       { return $this->hasMany(Goal::class, 'it_treatment_plan_id'); }
    public function objectives(): HasManyThrough
    {
        return $this->hasManyThrough(Objective::class, Goal::class, 'it_treatment_plan_id', 'it_goal_id');
    }
    public function signedByEmployee(): BelongsTo { return $this->belongsTo(Employee::class, 'signed_by'); }
    public function signedByUser(): BelongsTo     { return $this->belongsTo(User::class, 'signed_by_user_id'); }
}
