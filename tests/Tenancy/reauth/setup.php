<?php

use App\Models\User;
use App\Tenancy\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/bootstrap.php';

bootReauthProbeApp();

$tenant = Tenant::factory()->create();

TenantContext::run($tenant->id, function () use ($tenant) {
    User::factory()->create([
        'tenant_id' => $tenant->id,
        'role' => 'admin',
        'email' => 'admin@example.test',
        'password' => Hash::make('correct-password'),
    ]);
});

// Committed for real (this process has no RefreshDatabase transaction around
// it), so the login probe and the second-request probe, each its own process
// and connection, can read it.
echo json_encode(['slug' => $tenant->slug]);
