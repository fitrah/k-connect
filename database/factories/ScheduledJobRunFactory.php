<?php

namespace Database\Factories;

use App\Enums\ScheduledJobStatus;
use App\Models\Organization;
use App\Models\ScheduledJobRun;
use Illuminate\Support\Str;

/** @extends OrganizationOwnedFactory<ScheduledJobRun> */
class ScheduledJobRunFactory extends OrganizationOwnedFactory
{
    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'job_name' => 'threads:search-due-keywords', 'status' => ScheduledJobStatus::Succeeded, 'started_at' => now()->subSecond(), 'finished_at' => now(), 'duration_ms' => 100, 'processed_count' => 1, 'succeeded_count' => 1, 'failed_count' => 0, 'correlation_id' => (string) Str::uuid(), 'metadata' => []];
    }
}
