<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test case
|--------------------------------------------------------------------------
|
| Every Feature / Tenancy / RabbitMq test extends Tests\TestCase, which
| already includes RefreshesDatabaseAsMigrator (a migrator-aware wrapper
| around Laravel's RefreshDatabase). Pest's `in()` ignores a folder that does
| not exist, so deleting an optional module's test folder needs no change here.
|
*/

pest()->extend(TestCase::class)
    ->in('Feature', 'Tenancy', 'RabbitMq');

require __DIR__.'/Support/AuthTestHelpers.php';
