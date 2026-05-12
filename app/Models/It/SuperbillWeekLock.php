<?php

namespace App\Models\It;

use App\Models\Concerns\BelongsToClient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuperbillWeekLock extends Model
{
    use BelongsToClient;

    protected $table = 'it_superbill_week_locks';

    protected $fillable = [
        'client_id', 'week_start_date', 'locked_by', 'locked_at',
        'supervisor_name', 'notes',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'locked_at'       => 'datetime',
    ];

    public function locker(): BelongsTo { return $this->belongsTo(User::class, 'locked_by'); }
}
