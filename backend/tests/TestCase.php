<?php

namespace Tests;

use App\Support\LegacyAccess;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\EloquentGuardableColumnsReset;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            LegacyAccess::clearCache();
            EloquentGuardableColumnsReset::reset();
        }
    }

    protected function setUpTraits()
    {
        $this->ensureSafeTestingEnvironment();

        return parent::setUpTraits();
    }

    protected function ensureSafeTestingEnvironment(): void
    {
        if (app()->environment() !== 'testing') {
            throw new \Exception('Refusing to run tests outside testing environment.');
        }

        $defaultConn = config('database.default');
        $primaryDb = config("database.connections.{$defaultConn}.database");

        if ($defaultConn !== 'sqlite' || $primaryDb !== ':memory:') {
            throw new \Exception(
                "Refusing to run tests against non-isolated primary database [{$defaultConn}:{$primaryDb}]."
            );
        }

        $legacyDb = config('database.connections.legacy.database');
        if ($legacyDb === 'simrs_legacy_full' || $legacyDb === 'simrs_legacy_restored' || $legacyDb === 'simrs_legacy' || $legacyDb === 'bigsirs_dev') {
            throw new \Exception("Refusing to run tests against production-like legacy database {$legacyDb}.");
        }

        if ($legacyDb !== 'simrs_legacy_test' && $legacyDb !== ':memory:') {
            throw new \Exception('Refusing destructive legacy test outside simrs_legacy_test or :memory:.');
        }
    }
}
