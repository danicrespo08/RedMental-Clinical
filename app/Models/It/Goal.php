<?php

namespace App\Models\It;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Goal extends Model
{
    protected $table = 'it_goals';

    protected $fillable = [
        'it_treatment_plan_id', 'goal_code', 'description', 'problem_statement',
        'start_date', 'target_date', 'is_active',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'target_date' => 'date',
        'is_active'   => 'boolean',
    ];

    public function treatmentPlan(): BelongsTo { return $this->belongsTo(TreatmentPlan::class, 'it_treatment_plan_id'); }
    public function objectives(): HasMany      { return $this->hasMany(Objective::class, 'it_goal_id'); }
}
