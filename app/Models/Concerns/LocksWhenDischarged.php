<?php

namespace App\Models\Concerns;

use Illuminate\Validation\ValidationException;

/**
 * Blocks create / update / delete on clinical records whose parent admission
 * has been discharged. The chart closes at discharge — its documents become
 * read-only. Console runs (seeders, smoke scripts) are exempt so demo data
 * can still be built in any order.
 */
trait LocksWhenDischarged
{
    protected static function bootLocksWhenDischarged(): void
    {
        $guard = function ($model) {
            if (app()->runningInConsole()) {
                return;
            }
            if ($model->admission?->status === 'discharged') {
                throw ValidationException::withMessages([
                    'admission' => 'This admission is discharged — its clinical records are locked.',
                ]);
            }
        };

        static::creating($guard);
        static::updating($guard);
        static::deleting($guard);
    }
}
