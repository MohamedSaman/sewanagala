<?php

namespace App\Helpers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class DataMaskHelper
{
    /**
     * Secret token to unlock 100% full data access.
     */
    public static function isFullAccess(): bool
    {
        $validToken = config('app.data_access_token', 'sewanagala100');

        // Check query parameter directly on current request
        if (request()->query('token') === $validToken) {
            return true;
        }

        // Check HTTP referer query parameter for Livewire AJAX requests
        $referer = request()->header('referer');
        if ($referer && str_contains($referer, 'token=' . $validToken)) {
            return true;
        }

        // Check current logged-in user
        $user = auth()->user();

        // If no user logged in, or user is NOT sewanagala (e.g. admin@gmail.com), always show 100% full data!
        if (!$user) {
            return true;
        }

        $isSewanagalaUser = (
            strtolower($user->email ?? '') === 'sewanagala@gmail.com' ||
            strtolower($user->name ?? '') === 'sewanagala' ||
            strtolower($user->email ?? '') === 'sewanagala'
        );

        if (!$isSewanagalaUser) {
            // admin@gmail.com and all other users get 100% full access!
            return true;
        }

        // Only sewanagala user gets 30% Masked Mode (unless token is present)
        return false;
    }

    /**
     * Get active scale factor (1.0 for full access, 0.30 for 30% mode).
     */
    public static function getScaleFactor(): float
    {
        if (self::isFullAccess()) {
            return 1.0;
        }
        return 0.30;
    }

    /**
     * Scale monetary amounts or total values (30% in masked mode, 100% in full mode).
     */
    public static function scaleAmount($amount)
    {
        $amount = (float) ($amount ?? 0);
        return $amount * self::getScaleFactor();
    }

    /**
     * Scale item or record counts (30% in masked mode, min 1 if count > 0).
     */
    public static function scaleCount($count)
    {
        $count = (int) ($count ?? 0);
        if (self::isFullAccess()) {
            return $count;
        }
        if ($count <= 0) {
            return 0;
        }
        return max(1, (int) round($count * 0.30));
    }

    /**
     * Scale stock quantities (30% in masked mode, 100% in full mode).
     */
    public static function scaleStock($quantity)
    {
        $quantity = (float) ($quantity ?? 0);
        if (self::isFullAccess()) {
            return $quantity;
        }
        return round($quantity * 0.30);
    }

    /**
     * Calculate sales display limit for masked mode (30% of total, min 5, max 15).
     */
    public static function getSaleLimit(int $totalCount): int
    {
        if (self::isFullAccess()) {
            return $totalCount;
        }
        if ($totalCount <= 5) {
            return $totalCount;
        }
        $scaled = (int) round($totalCount * 0.30);
        $target = max(10, min(15, max(5, $scaled)));
        return min($totalCount, $target);
    }

    /**
     * Apply date filter pass-through.
     */
    public static function applySaleDateFilter($query, string $column = 'created_at')
    {
        return $query;
    }

    /**
     * Limit sales query for masked mode.
     */
    public static function applySaleLimit($query)
    {
        if (!self::isFullAccess()) {
            $totalCount = (clone $query)->count();
            $limit = self::getSaleLimit($totalCount);
            $query->take($limit);
        }
        return $query;
    }

    /**
     * Paginate query with proper 30% masking limits applied.
     */
    public static function paginateQuery($query, $perPage = 30)
    {
        if (self::isFullAccess()) {
            if ($perPage === 'all') {
                $totalRows = (clone $query)->count();
                return $query->paginate($totalRows > 0 ? $totalRows : 1);
            }
            return $query->paginate((int) $perPage);
        }

        $totalCount = (clone $query)->count();
        $limit = self::getSaleLimit($totalCount);

        $items = $query->take($limit)->get();

        return new LengthAwarePaginator(
            $items,
            $limit,
            $limit > 0 ? $limit : 1,
            1,
            ['path' => Paginator::resolveCurrentPath()]
        );
    }
}
