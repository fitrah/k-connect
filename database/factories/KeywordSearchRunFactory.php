<?php

namespace Database\Factories;

use App\Enums\ScheduledJobStatus;
use App\Models\Keyword;
use App\Models\KeywordSearchRun;
use App\Models\Organization;

/** @extends OrganizationOwnedFactory<KeywordSearchRun> */
class KeywordSearchRunFactory extends OrganizationOwnedFactory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'keyword_id' => fn (array $attributes): int => Keyword::factory()->create(['organization_id' => $attributes['organization_id']])->id,
            'status' => ScheduledJobStatus::Succeeded,
            'request_metadata' => ['search_type' => 'RECENT'],
            'result_count' => 1,
            'new_post_count' => 1,
            'quota_estimate' => 1,
            'duration_ms' => 100,
            'started_at' => now()->subSecond(),
            'finished_at' => now(),
        ];
    }

    public function running(): static
    {
        return $this->state(fn (): array => ['status' => ScheduledJobStatus::Running, 'finished_at' => null]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => ['status' => ScheduledJobStatus::Failed, 'error_code' => 'search_failed']);
    }
}
