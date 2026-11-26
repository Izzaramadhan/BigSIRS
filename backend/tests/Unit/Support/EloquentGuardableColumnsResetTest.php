<?php

namespace Tests\Unit\Support;

use App\Models\Legacy\LegacyPerson;
use App\Support\LegacyAccess;
use Illuminate\Database\Eloquent\Model;
use ReflectionProperty;
use Tests\Support\EloquentGuardableColumnsReset;
use Tests\TestCase;

class EloquentGuardableColumnsResetTest extends TestCase
{
    public function test_legacy_access_clear_cache_does_not_mutate_eloquent_guardable_columns(): void
    {
        $property = new ReflectionProperty(Model::class, 'guardableColumns');
        $property->setValue(null, [LegacyPerson::class => ['id', 'nama']]);

        LegacyAccess::clearCache();

        $this->assertSame([LegacyPerson::class => ['id', 'nama']], $property->getValue(null));

        EloquentGuardableColumnsReset::reset();

        $this->assertSame([], $property->getValue(null));
        $this->assertFalse(Model::isUnguarded());
    }
}
