<?php

namespace App\Models\Psr;

use App\Models\Hhrr\Clinic;
use App\Models\Concerns\BelongsToClient;
use App\Models\Hhrr\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupSession extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $table = 'psr_group_sessions';

    public const STATUSES = [
        'scheduled'   => 'Scheduled',
        'in_progress' => 'In progress',
        'completed'   => 'Completed',
        'cancelled'   => 'Cancelled',
    ];

    protected $fillable = [
        'client_id', 'clinic_id',
        'session_date', 'start_time', 'end_time',
        'title', 'session_type', 'service_code', 'modifier', 'place_of_service',
        'lead_therapist_id', 'co_therapist_id',
        'max_capacity',
        'break_start_time', 'break_end_time', 'break_minutes',
        'activities',
        'session_summary', 'notes',
        'status', 'is_signed', 'signed_by', 'signed_at',
        'created_by',
    ];

    protected $casts = [
        'session_date' => 'date',
        'is_signed'    => 'boolean',
        'signed_at'    => 'datetime',
        'activities'   => 'array',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function getDurationMinutesAttribute(): int
    {
        if (! $this->start_time || ! $this->end_time) return 0;
        $start = \Carbon\Carbon::parse($this->start_time);
        $end   = \Carbon\Carbon::parse($this->end_time);
        return max(0, (int) $end->diffInMinutes($start) - (int) ($this->break_minutes ?? 0));
    }

    public function clinic(): BelongsTo         { return $this->belongsTo(Clinic::class); }
    public function leadTherapist(): BelongsTo  { return $this->belongsTo(Employee::class, 'lead_therapist_id'); }
    public function coTherapist(): BelongsTo    { return $this->belongsTo(Employee::class, 'co_therapist_id'); }
    public function attendees(): HasMany        { return $this->hasMany(GroupSessionAttendee::class, 'psr_group_session_id'); }
    public function progressNotes(): HasMany    { return $this->hasMany(ProgressNote::class, 'psr_group_session_id'); }
    public function signer(): BelongsTo         { return $this->belongsTo(User::class, 'signed_by'); }
    public function creator(): BelongsTo        { return $this->belongsTo(User::class, 'created_by'); }
}
