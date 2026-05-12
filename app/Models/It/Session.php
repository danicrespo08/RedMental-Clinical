<?php

namespace App\Models\It;

use App\Models\Concerns\BelongsToClient;
use App\Models\Hhrr\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Session extends Model
{
    use BelongsToClient;

    protected $table = 'it_sessions';

    protected $fillable = [
        'client_id', 'it_admission_id', 'therapist_id',
        'session_date', 'start_time', 'end_time', 'duration_minutes',
        'cpt_code', 'modifier', 'place_of_service', 'units',
        'subjective', 'objective', 'assessment', 'plan', 'goals_addressed',
    ];

    protected $casts = [
        'session_date' => 'date',
    ];

    public function admission(): BelongsTo { return $this->belongsTo(Admission::class, 'it_admission_id'); }
    public function therapist(): BelongsTo { return $this->belongsTo(Employee::class, 'therapist_id'); }
}
