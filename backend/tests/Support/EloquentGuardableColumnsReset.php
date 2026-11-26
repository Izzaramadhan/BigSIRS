<?php

namespace Tests\Support;

use Illuminate\Database\Eloquent\Model;
use ReflectionProperty;
use RuntimeException;

/**
 * Test-only helper to reset Eloquent's static `$guardableColumns` cache.
 *
 * Laravel caches per-model column listings across the PHP process via
 * `Concerns\GuardsAttributes::$guardableColumns`. When tests recreate
 * SQLite `:memory:` legacy schemas with differing column subsets in one run,
 * resetting this cache prevents stale column sets from silently dropping new
 * columns without globally disabling mass-assignment protection.
 */
final class EloquentGuardableColumnsReset
{
    /**
     * Reset cached guardable column listings across all Eloquent models.
     */
    public static function reset(): void
    {
        if (! property_exists(Model::class, 'guardableColumns')) {
            throw new RuntimeException(
                'Expected Model::$guardableColumns cache property is missing; update test isolation helper for this Laravel version.'
            );
        }

        $property = new ReflectionProperty(Model::class, 'guardableColumns');
        $property->setValue(null, []);
    }
}
