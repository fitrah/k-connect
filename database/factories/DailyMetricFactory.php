<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\DailyMetric;
use App\Models\Organization;

/** @extends OrganizationOwnedFactory<DailyMetric> */
class DailyMetricFactory extends OrganizationOwnedFactory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'campaign_id' => fn (array $attributes): int => Campaign::factory()->create(['organization_id' => $attributes['organization_id']])->id,
            'metric_date' => today(),
            'searches' => 1,
            'discovered_posts' => 1,
            'relevant_posts' => 1,
            'drafts_generated' => 1,
            'approved_drafts' => 0,
            'rejected_drafts' => 0,
            'published_replies' => 0,
            'failed_replies' => 0,
            'llm_input_tokens' => 10,
            'llm_output_tokens' => 10,
            'estimated_cost' => 0,
        ];
    }
}
