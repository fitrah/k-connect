<?php

namespace Database\Factories;

use App\Enums\MappingStatus;
use App\Models\Organization;
use App\Models\ResponseMapping;

/** @extends OrganizationOwnedFactory<ResponseMapping> */
class ResponseMappingFactory extends OrganizationOwnedFactory
{
    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'campaign_id' => null, 'name' => fake()->unique()->words(3, true), 'priority' => 100, 'status' => MappingStatus::Draft, 'applicable_intents' => ['seeking_recommendation'], 'human_approval_required' => true, 'auto_reply_allowed' => false, 'confidence_threshold' => 0.8];
    }
}
