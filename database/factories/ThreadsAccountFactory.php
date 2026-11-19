<?php

namespace Database\Factories;

use App\Enums\ThreadsAccountStatus;
use App\Models\Organization;
use App\Models\ThreadsAccount;

/** @extends OrganizationOwnedFactory<ThreadsAccount> */
class ThreadsAccountFactory extends OrganizationOwnedFactory
{
    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'threads_user_id' => (string) fake()->unique()->randomNumber(8, true), 'username' => fake()->unique()->userName(), 'access_token' => null, 'token_expires_at' => now()->addMonth(), 'scopes' => ['threads_basic'], 'status' => ThreadsAccountStatus::Connected, 'is_publishing_enabled' => false];
    }
}
