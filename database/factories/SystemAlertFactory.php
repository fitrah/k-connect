<?php

namespace Database\Factories;

use App\Enums\AlertStatus;
use App\Models\Organization;
use App\Models\SystemAlert;

/** @extends OrganizationOwnedFactory<SystemAlert> */
class SystemAlertFactory extends OrganizationOwnedFactory
{
    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'type' => 'job_failure', 'severity' => 'warning', 'status' => AlertStatus::Open, 'title' => fake()->sentence(4), 'message' => fake()->sentence(), 'context' => []];
    }
}
