<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
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
        if ($legacyDb === 'simrs_legacy_full' || $legacyDb === 'simrs_legacy_restored') {
            throw new \Exception("Refusing to run tests against production-like legacy database {$legacyDb}.");
        }

        if ($legacyDb !== 'simrs_legacy_test') {
            throw new \Exception('Refusing destructive legacy test outside simrs_legacy_test.');
        }
    }
}
