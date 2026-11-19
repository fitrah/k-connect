<?php

namespace Database\Factories;

use App\Enums\KeywordStatus;
use App\Models\Campaign;
use App\Models\Keyword;
use App\Models\Organization;

/** @extends OrganizationOwnedFactory<Keyword> */
class KeywordFactory extends OrganizationOwnedFactory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'campaign_id' => fn (array $attributes): int => Campaign::factory()->create(['organization_id' => $attributes['organization_id']])->id,
            'response_mapping_id' => null,
            'query' => fake()->unique()->words(2, true),
            'search_mode' => 'KEYWORD',
            'search_type' => 'RECENT',
            'media_type' => 'all',
            'include_terms' => [],
            'exclude_terms' => [],
            'language' => 'id',
            'interval_minutes' => 15,
            'priority' => 100,
            'status' => KeywordStatus::Draft,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['status' => KeywordStatus::Active]);
    }

    public function paused(): static
    {
        return $this->state(fn (): array => ['status' => KeywordStatus::Paused]);
    }
}
