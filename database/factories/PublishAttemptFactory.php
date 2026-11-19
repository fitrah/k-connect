<?php

namespace Database\Factories;

use App\Enums\PublishAttemptStatus;
use App\Models\Organization;
use App\Models\PublishAttempt;
use App\Models\ReplyDraft;
use App\Models\ThreadsAccount;

/** @extends OrganizationOwnedFactory<PublishAttempt> */
class PublishAttemptFactory extends OrganizationOwnedFactory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'reply_draft_id' => fn (array $attributes): int => ReplyDraft::factory()->create(['organization_id' => $attributes['organization_id']])->id,
            'threads_account_id' => fn (array $attributes): int => ThreadsAccount::factory()->create(['organization_id' => $attributes['organization_id']])->id,
            'attempt_number' => 1,
            'status' => PublishAttemptStatus::Approved,
            'is_retryable' => false,
            'started_at' => now(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['status' => PublishAttemptStatus::Published, 'finished_at' => now()]);
    }

    public function retryableFailure(): static
    {
        return $this->state(fn (): array => ['status' => PublishAttemptStatus::FailedRetryable, 'is_retryable' => true]);
    }
}
