<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\ReplyDraft;
use App\Models\ReplyRevision;

/** @extends OrganizationOwnedFactory<ReplyRevision> */
class ReplyRevisionFactory extends OrganizationOwnedFactory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'reply_draft_id' => fn (array $attributes): int => ReplyDraft::factory()->create(['organization_id' => $attributes['organization_id']])->id,
            'created_by' => null,
            'version' => 1,
            'content' => fake()->sentence(),
            'reason' => 'Operator edit',
        ];
    }
}
