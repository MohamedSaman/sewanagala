<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'description',
        'is_blocked_for_cheque',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'is_blocked_for_cheque' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if a specific date is a blocked holiday / leave date.
     */
    public static function isHoliday($date): bool
    {
        if (empty($date)) {
            return false;
        }

        try {
            $formatted = Carbon::parse($date)->format('Y-m-d');
            return static::whereDate('date', $formatted)
                ->where('is_blocked_for_cheque', true)
                ->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the reason/description of a holiday on a specific date.
     */
    public static function getHolidayReason($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            $formatted = Carbon::parse($date)->format('Y-m-d');
            $holiday = static::whereDate('date', $formatted)->first();
            return $holiday ? ($holiday->description ?: 'Holiday / Poya Day') : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get an array of all blocked dates in Y-m-d format for datepicker disabling.
     */
    public static function getBlockedDates(): array
    {
        return static::where('is_blocked_for_cheque', true)
            ->pluck('date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->values()
            ->toArray();
    }
}
