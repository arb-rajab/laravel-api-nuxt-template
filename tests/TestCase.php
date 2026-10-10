<?php

namespace Tests;

use App\Tenancy\CurrentTenant;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\RefreshesDatabaseAsMigrator;

abstract class TestCase extends BaseTestCase
{
    use RefreshesDatabaseAsMigrator;

    protected function tearDown(): void
    {
        // CurrentTenant is process-local static state and Pest runs every
        // test in the same PHP process, so a test that sets it directly (or
        // throws before TenantContext::run() restores it) must never leak
        // tenant context into the next test. Guarded so this file still
        // works if the optional tenancy module is deleted.
        if (class_exists(CurrentTenant::class)) {
            CurrentTenant::clear();
        }

        parent::tearDown();
    }
}
