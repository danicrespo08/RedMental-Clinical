<?php

namespace App\Models\Tcm;

use App\Models\Concerns\BelongsToClient;
use App\Models\Hhrr\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    use BelongsToClient;

    protected $table = 'tcm_contacts';

    protected $fillable = [
        'client_id', 'tcm_admission_id', 'case_manager_id',
        'contact_at', 'contact_type', 'duration_minutes',
        'cpt_code', 'units', 'place_of_service',
        'with_whom', 'goals_addressed', 'summary', 'next_actions',
    ];

    protected $casts = [
        'contact_at' => 'datetime',
    ];

    public function admission(): BelongsTo  { return $this->belongsTo(Admission::class, 'tcm_admission_id'); }
    public function caseManager(): BelongsTo { return $this->belongsTo(Employee::class, 'case_manager_id'); }

    public const CONTACT_TYPES = [
        'in_person'  => 'In person',
        'phone'      => 'Phone call',
        'video'      => 'Video call',
        'email'      => 'Email',
        'collateral' => 'Collateral contact',
        'home_visit' => 'Home visit',
    ];
}
