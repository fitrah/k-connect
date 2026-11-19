<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\QuotaUsage;
use App\Models\ThreadsAccount;

/** @extends OrganizationOwnedFactory<QuotaUsage> */
class QuotaUsageFactory extends OrganizationOwnedFactory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'threads_account_id' => fn (array $attributes): int => ThreadsAccount::factory()->create(['organization_id' => $attributes['organization_id']])->id,
            'operation' => 'keyword_search',
            'window_started_at' => now()->subDay(),
            'window_ends_at' => now(),
            'used' => 1,
            'limit' => 2200,
            'metadata' => [],
        ];
    }
}
