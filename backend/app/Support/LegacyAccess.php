<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class LegacyAccess
{
    /**
     * Cache table existence checks for the lifecycle of the request.
     *
     * @var array<string, bool>
     */
    protected static array $tableExistsCache = [];

    /**
     * Check if direct legacy access feature is enabled.
     */
    public static function isEnabled(): bool
    {
        return (bool) config('database.use_direct_legacy', env('USE_DIRECT_LEGACY', true));
    }

    /**
     * Check if a specific table exists in the legacy database connection.
     */
    public static function hasTable(string $table): bool
    {
        if (isset(self::$tableExistsCache[$table])) {
            return self::$tableExistsCache[$table];
        }

        try {
            return self::$tableExistsCache[$table] = Schema::connection('legacy')->hasTable($table);
        } catch (\Throwable) {
            return self::$tableExistsCache[$table] = false;
        }
    }

    /**
     * Check if direct legacy access is enabled and the table is present.
     */
    public static function isAvailable(string $table): bool
    {
        return self::isEnabled() && self::hasTable($table);
    }

    /**
     * Clear cached table checks (useful during tests).
     */
    public static function clearCache(): void
    {
        self::$tableExistsCache = [];
    }
}

