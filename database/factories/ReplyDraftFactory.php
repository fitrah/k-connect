<?php

namespace Database\Factories;

use App\Enums\DraftStatus;
use App\Models\DiscoveredPost;
use App\Models\MappingVersion;
use App\Models\Organization;
use App\Models\PostClassification;
use App\Models\PromptVersion;
use App\Models\ReplyDraft;
use App\Models\ResponseMapping;

/** @extends OrganizationOwnedFactory<ReplyDraft> */
class ReplyDraftFactory extends OrganizationOwnedFactory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'discovered_post_id' => fn (array $attributes): int => DiscoveredPost::factory()->create(['organization_id' => $attributes['organization_id']])->id,
            'post_classification_id' => fn (array $attributes): int => PostClassification::factory()
                ->for(DiscoveredPost::withoutGlobalScopes()->findOrFail($attributes['discovered_post_id']), 'discoveredPost')
                ->create(['organization_id' => $attributes['organization_id']])->id,
            'mapping_version_id' => function (array $attributes): int {
                $mapping = ResponseMapping::factory()->create(['organization_id' => $attributes['organization_id']]);

                return MappingVersion::factory()->for($mapping, 'responseMapping')->create(['organization_id' => $attributes['organization_id']])->id;
            },
            'prompt_version_id' => fn (array $attributes): int => PromptVersion::factory()->create(['organization_id' => $attributes['organization_id'], 'type' => 'generation'])->id,
            'status' => DraftStatus::PendingReview,
            'content' => fake()->sentence(),
            'language' => 'id',
            'warnings' => [],
            'requires_human_review' => true,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => DraftStatus::Approved, 'approved_at' => now()]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => ['status' => DraftStatus::Rejected, 'rejected_at' => now(), 'rejection_reason' => 'Not suitable']);
    }
}
