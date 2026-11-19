<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Organization;
use Illuminate\Support\Str;

/** @extends OrganizationOwnedFactory<AuditLog> */
class AuditLogFactory extends OrganizationOwnedFactory
{
    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'actor_id' => null, 'action' => 'campaign.created', 'target_type' => 'campaign', 'target_id' => '1', 'before' => null, 'after' => ['status' => 'draft'], 'request_id' => (string) Str::uuid(), 'correlation_id' => (string) Str::uuid(), 'ip_address' => '127.0.0.1', 'user_agent' => 'PHPUnit', 'created_at' => now()];
    }
}
