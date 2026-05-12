<?php

namespace App\Models\Psr;

use App\Models\Hhrr\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class TreatmentPlan extends Model
{
    protected $table = 'psr_treatment_plans';

    public const STRENGTHS = [
        'accepts_feedback'      => 'Accepts feedback and help',
        'intelligent'           => 'Intelligent',
        'responsible'           => 'Responsible',
        'capable_independence'  => 'Capable of independence',
        'motivated_change'      => 'Motivated for change',
        'sociable'              => 'Sociable',
        'clear_thinking'        => 'Clear thinking',
        'physically_healthy'    => 'Physically healthy',
        'stable_living'         => 'Stable living environment',
        'confident'             => 'Confident',
        'positive_support'      => 'Positive support network',
        'stable_work'           => 'Stable work history',
        'expressive'            => 'Expressive and articulate',
        'reasonable_judgment'   => 'Reasonable judgment',
        'supportive_family'     => 'Supportive family',
        'good_hygiene'          => 'Good personal care habits',
        'reliable'              => 'Reliable',
        'varied_interests'      => 'Varied interests',
        'insightful'            => 'Insightful',
        'coping_skills'         => 'Coping skills / resiliency',
        'positive_therapist'    => 'Positive relationship with therapist',
    ];

    public const WEAKNESSES = [
        'chaotic_living'        => 'Chaotic living',
        'impulsive'             => 'Impulsive',
        'indecisive'            => 'Indecisive',
        'concrete_thinking'     => 'Concrete thinking',
        'intellectual_deficit'  => 'Intellectual deficit',
        'irresponsible'         => 'Irresponsible',
        'defensive'             => 'Defensive',
        'lack_insight'          => 'Lack of insight',
        'unreliable'            => 'Unreliable',
        'dependent'             => 'Dependent',
        'lack_social_skills'    => 'Lack of social skills',
        'needs_supervision'     => 'Needs close supervision',
        'distrustful'           => 'Distrustful',
        'negative_peers'        => 'Negative peer group',
        'no_support'            => 'No support network',
        'hostile'               => 'Hostile',
        'nonsupportive_family'  => 'Non-supportive family',
        'no_motivation'         => 'No motivation to change',
        'poor_health'           => 'Poor health',
        'poor_judgment'         => 'Poor judgment',
        'unstable_employment'   => 'Unstable employment history',
        'limited_financial'     => 'Limited financial resources',
        'narrow_interests'      => 'Very narrow interests',
    ];

    public const SERVICES = [
        'psychiatric_eval'      => 'Psychiatric evaluation',
        'medication_mgmt'       => 'Medication management',
        'biopsychosocial'       => 'Biopsychosocial evaluation',
        'individual_therapy'    => 'Individual therapy',
        'group_therapy'         => 'Group therapy',
        'family_therapy'        => 'Family therapy',
        'psr_adult'             => 'PSR services (adult)',
        'clubhouse'             => 'Clubhouse services',
        'case_management'       => 'Mental health targeted case management',
        'psr_children'          => 'PSR services (children)',
        'adult_day_care'        => 'Adult day care services',
        'tbos'                  => 'TBOS services',
    ];

    protected $fillable = [
        'psr_admission_id',
        'start_date', 'end_date',
        'strengths', 'weaknesses', 'services',
        'strengths_other', 'weaknesses_other',
        'long_term_goal', 'discharge_criteria',
        'is_signed', 'signed_at', 'signed_by', 'signed_by_user_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'strengths'  => 'array',
        'weaknesses' => 'array',
        'services'   => 'array',
        'is_signed'  => 'boolean',
        'signed_at'  => 'datetime',
    ];

    public function admission(): BelongsTo  { return $this->belongsTo(Admission::class, 'psr_admission_id'); }
    public function goals(): HasMany        { return $this->hasMany(Goal::class, 'psr_treatment_plan_id'); }
    public function objectives(): HasManyThrough
    {
        return $this->hasManyThrough(Objective::class, Goal::class, 'psr_treatment_plan_id', 'psr_goal_id');
    }
    public function signedByEmployee(): BelongsTo { return $this->belongsTo(Employee::class, 'signed_by'); }
    public function signedByUser(): BelongsTo   { return $this->belongsTo(User::class, 'signed_by_user_id'); }
}
