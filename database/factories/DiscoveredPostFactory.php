<?php

namespace Database\Factories;

use App\Enums\DiscoveredPostStatus;
use App\Models\DiscoveredPost;
use App\Models\Organization;
use App\Models\ThreadsAccount;

/** @extends OrganizationOwnedFactory<DiscoveredPost> */
class DiscoveredPostFactory extends OrganizationOwnedFactory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'threads_account_id' => fn (array $attributes): int => ThreadsAccount::factory()->create(['organization_id' => $attributes['organization_id']])->id,
            'threads_media_id' => (string) fake()->unique()->randomNumber(9, true),
            'author_threads_id' => (string) fake()->randomNumber(8, true),
            'author_username' => fake()->userName(),
            'caption' => fake()->sentence(),
            'media_type' => 'TEXT_POST',
            'permalink' => fake()->url(),
            'posted_at' => now(),
            'status' => DiscoveredPostStatus::New,
            'metadata' => [],
        ];
    }

    public function awaitingClassification(): static
    {
        return $this->state(fn (): array => ['status' => DiscoveredPostStatus::AwaitingClassification]);
    }

    public function relevant(): static
    {
        return $this->state(fn (): array => ['status' => DiscoveredPostStatus::ClassifiedRelevant]);
    }
}
