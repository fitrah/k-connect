<?php

namespace Database\Factories;

use App\Models\DiscoveredPost;
use App\Models\Organization;
use App\Models\PostClassification;

/** @extends OrganizationOwnedFactory<PostClassification> */
class PostClassificationFactory extends OrganizationOwnedFactory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'discovered_post_id' => fn (array $attributes): int => DiscoveredPost::factory()->create(['organization_id' => $attributes['organization_id']])->id,
            'prompt_version_id' => null,
            'is_relevant' => true,
            'confidence' => 0.9,
            'intent' => 'seeking_recommendation',
            'language' => 'id',
            'sentiment' => 'neutral',
            'risk_flags' => [],
            'reason' => fake()->sentence(),
            'recommended_action' => 'draft_reply',
            'model' => 'demo-model',
            'latency_ms' => 100,
            'input_tokens' => 10,
            'output_tokens' => 10,
            'raw_output' => ['relevant' => true],
        ];
    }

    public function irrelevant(): static
    {
        return $this->state(fn (): array => ['is_relevant' => false, 'recommended_action' => 'ignore']);
    }
}
