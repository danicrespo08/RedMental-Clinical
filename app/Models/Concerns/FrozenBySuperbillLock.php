<?php

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Blocks create / update / delete on service-log entries whose service week
 * has been locked in the superbill. Checks both the entry's current and
 * original date so a frozen entry cannot be moved out of a locked week.
 * Console runs (seeders, smoke scripts) are exempt.
 */
trait FrozenBySuperbillLock
{
    protected static function bootFrozenBySuperbillLock(): void
    {
        $guard = function ($model) {
            if (app()->runningInConsole()) {
                return;
            }
            $dates = array_filter([$model->service_date, $model->getOriginal('service_date')]);
            foreach ($dates as $date) {
                if ($model->superbillWeekIsLocked(Carbon::parse($date))) {
                    throw ValidationException::withMessages([
                        'service_date' => 'This week is locked in the superbill — its service-log entries are frozen.',
                    ]);
                }
            }
        };

        static::creating($guard);
        static::updating($guard);
        static::deleting($guard);
    }

    protected function superbillWeekIsLocked(Carbon $date): bool
    {
        $lockClass = preg_replace('/\\\\[^\\\\]+$/', '\\\\SuperbillWeekLock', static::class);
        $monday    = $date->copy()->startOfWeek(Carbon::MONDAY)->toDateString();

        $query = $lockClass::query()->whereDate('week_start_date', $monday);

        // PSR locks can target a single clinic; a null clinic_id freezes the whole client.
        if (in_array('clinic_id', (new $lockClass)->getFillable(), true)) {
            $query->where(fn ($q) => $q->whereNull('clinic_id')->orWhere('clinic_id', $this->clinic_id));
        }

        return $query->exists();
    }
}
