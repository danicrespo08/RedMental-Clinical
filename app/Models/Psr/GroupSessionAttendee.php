<?php

namespace App\Models\Psr;

use App\Models\Hhrr\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupSessionAttendee extends Model
{
    protected $table = 'psr_group_session_attendees';

    public const ATTENDANCE = [
        'present'    => 'Present',
        'absent'     => 'Absent',
        'late'       => 'Late',
        'left_early' => 'Left early',
    ];

    protected $fillable = [
        'psr_group_session_id', 'psr_admission_id', 'patient_id',
        'attendance_status', 'check_in_time', 'check_out_time',
        'schedule_segments', 'units', 'participation_level',
        'individual_notes', 'created_by',
    ];

    protected $casts = [
        'schedule_segments' => 'array',
    ];

    public function session(): BelongsTo   { return $this->belongsTo(GroupSession::class, 'psr_group_session_id'); }
    public function admission(): BelongsTo { return $this->belongsTo(Admission::class, 'psr_admission_id'); }
    public function patient(): BelongsTo   { return $this->belongsTo(Patient::class); }
    public function creator(): BelongsTo   { return $this->belongsTo(User::class, 'created_by'); }
}
