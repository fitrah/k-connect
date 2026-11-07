<?php

namespace Database\Factories;

use App\Models\MappingVersion;
use App\Models\Organization;
use App\Models\ResponseMapping;

/** @extends OrganizationOwnedFactory<MappingVersion> */
class MappingVersionFactory extends OrganizationOwnedFactory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'response_mapping_id' => fn (array $attributes): int => ResponseMapping::factory()->create(['organization_id' => $attributes['organization_id']])->id,
            'created_by' => null,
            'version' => 1,
            'configuration' => ['tone' => 'helpful', 'max_reply_length' => 350],
            'change_summary' => 'Initial version',
        ];
    }
}
