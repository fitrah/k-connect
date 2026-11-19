<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Organization;

/** @extends OrganizationOwnedFactory<Campaign> */
class CampaignFactory extends OrganizationOwnedFactory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'threads_account_id' => null,
            'default_response_mapping_id' => null,
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'status' => CampaignStatus::Draft,
            'default_language' => 'id',
            'timezone' => 'UTC',
            'approval_mode' => 'human',
            'confidence_threshold' => 0.8,
            'hourly_reply_limit' => 0,
            'daily_reply_limit' => 0,
        ];
    }
}
