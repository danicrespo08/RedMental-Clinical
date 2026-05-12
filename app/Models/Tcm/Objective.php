<?php

namespace App\Models\Tcm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Objective extends Model
{
    protected $table = 'tcm_objectives';

    protected $fillable = [
        'tcm_goal_id', 'objective_code', 'description',
        'intervention_type', 'intervention_description',
        'start_date', 'target_date', 'is_active',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'target_date' => 'date',
        'is_active'   => 'boolean',
    ];

    public function goal(): BelongsTo { return $this->belongsTo(Goal::class, 'tcm_goal_id'); }
}
