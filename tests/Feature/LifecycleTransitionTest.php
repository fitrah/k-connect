<?php

namespace Tests\Feature;

use App\Enums\AlertStatus;
use App\Enums\CampaignStatus;
use App\Enums\DiscoveredPostStatus;
use App\Enums\DraftStatus;
use App\Enums\KeywordStatus;
use App\Enums\LifecycleStatus;
use App\Enums\MappingStatus;
use App\Enums\PromptStatus;
use App\Enums\PublishAttemptStatus;
use App\Enums\ScheduledJobStatus;
use App\Enums\ThreadsAccountStatus;
use App\Models\Campaign;
use App\Models\DiscoveredPost;
use App\Models\Keyword;
use App\Models\KeywordSearchRun;
use App\Models\Organization;
use App\Models\PromptVersion;
use App\Models\PublishAttempt;
use App\Models\ReplyDraft;
use App\Models\ResponseMapping;
use App\Models\ScheduledJobRun;
use App\Models\SystemAlert;
use App\Models\ThreadsAccount;
use App\Support\OrganizationContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use LogicException;
use Tests\TestCase;

class LifecycleTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_material_lifecycle_enum_declares_legal_and_terminal_transitions(): void
    {
        $this->assertTrue(CampaignStatus::Draft->canTransitionTo(CampaignStatus::Active));
        $this->assertFalse(CampaignStatus::Archived->canTransitionTo(CampaignStatus::Active));
        $this->assertTrue(KeywordStatus::Error->canTransitionTo(KeywordStatus::Active));
        $this->assertFalse(KeywordStatus::Archived->canTransitionTo(KeywordStatus::Draft));
        $this->assertTrue(DiscoveredPostStatus::New->canTransitionTo(DiscoveredPostStatus::AwaitingClassification));
        $this->assertFalse(DiscoveredPostStatus::Blocked->canTransitionTo(DiscoveredPostStatus::New));
        $this->assertTrue(DraftStatus::PendingReview->canTransitionTo(DraftStatus::Approved));
        $this->assertFalse(DraftStatus::Published->canTransitionTo(DraftStatus::Edited));
        $this->assertTrue(MappingStatus::Draft->canTransitionTo(MappingStatus::Active));
        $this->assertFalse(MappingStatus::Archived->canTransitionTo(MappingStatus::Active));
        $this->assertTrue(PromptStatus::Draft->canTransitionTo(PromptStatus::Active));
        $this->assertFalse(PromptStatus::Retired->canTransitionTo(PromptStatus::Active));
        $this->assertTrue(PublishAttemptStatus::FailedRetryable->canTransitionTo(PublishAttemptStatus::CreatingContainer));
        $this->assertFalse(PublishAttemptStatus::Published->canTransitionTo(PublishAttemptStatus::Publishing));
        $this->assertTrue(ScheduledJobStatus::Pending->canTransitionTo(ScheduledJobStatus::Running));
        $this->assertFalse(ScheduledJobStatus::Succeeded->canTransitionTo(ScheduledJobStatus::Running));
        $this->assertTrue(AlertStatus::Open->canTransitionTo(AlertStatus::Acknowledged));
        $this->assertFalse(AlertStatus::Resolved->canTransitionTo(AlertStatus::Open));
        $this->assertTrue(ThreadsAccountStatus::Expiring->canTransitionTo(ThreadsAccountStatus::Connected));
        $this->assertFalse(ThreadsAccountStatus::Revoked->canTransitionTo(ThreadsAccountStatus::Connected));
    }

    public function test_every_lifecycle_state_handles_every_possible_next_state(): void
    {
        /** @var list<class-string<LifecycleStatus>> $enumClasses */
        $enumClasses = [
            AlertStatus::class,
            CampaignStatus::class,
            DiscoveredPostStatus::class,
            DraftStatus::class,
            KeywordStatus::class,
            MappingStatus::class,
            PromptStatus::class,
            PublishAttemptStatus::class,
            ScheduledJobStatus::class,
            ThreadsAccountStatus::class,
        ];

        $checkedTransitions = 0;

        foreach ($enumClasses as $enumClass) {
            foreach ($enumClass::cases() as $current) {
                foreach ($enumClass::cases() as $next) {
                    $current->canTransitionTo($next);
                    $checkedTransitions++;
                }
            }
        }

        $this->assertSame(419, $checkedTransitions);
    }

    public function test_models_persist_every_legal_lifecycle_transition(): void
    {
        $models = [
            [Campaign::factory()->create(['status' => CampaignStatus::Draft]), CampaignStatus::Active],
            [Keyword::factory()->create(['status' => KeywordStatus::Error]), KeywordStatus::Active],
            [DiscoveredPost::factory()->create(['status' => DiscoveredPostStatus::New]), DiscoveredPostStatus::AwaitingClassification],
            [ReplyDraft::factory()->create(['status' => DraftStatus::PendingReview]), DraftStatus::Approved],
            [ResponseMapping::factory()->create(['status' => MappingStatus::Draft]), MappingStatus::Active],
            [PromptVersion::factory()->create(['status' => PromptStatus::Draft]), PromptStatus::Active],
            [PublishAttempt::factory()->create(['status' => PublishAttemptStatus::FailedRetryable]), PublishAttemptStatus::CreatingContainer],
            [ScheduledJobRun::factory()->create(['status' => ScheduledJobStatus::Pending]), ScheduledJobStatus::Running],
            [KeywordSearchRun::factory()->create(['status' => ScheduledJobStatus::Pending]), ScheduledJobStatus::Running],
            [SystemAlert::factory()->create(['status' => AlertStatus::Open]), AlertStatus::Acknowledged],
            [ThreadsAccount::factory()->create(['status' => ThreadsAccountStatus::Expiring]), ThreadsAccountStatus::Connected],
        ];

        foreach ($models as [$model, $legalStatus]) {
            app(OrganizationContext::class)->set(Organization::query()->findOrFail($model->organization_id));

            $this->assertTrue($model->update(['status' => $legalStatus]));

            $persistedModel = $model->fresh();

            $this->assertNotNull($persistedModel);
            $this->assertSame($legalStatus, $persistedModel->status);
        }
    }

    public function test_legal_model_transitions_preserve_update_event_timestamp_and_change_semantics(): void
    {
        CarbonImmutable::setTestNow('2026-11-07 10:00:00');

        try {
            $campaign = Campaign::factory()->create(['status' => CampaignStatus::Draft]);
            app(OrganizationContext::class)->set(Organization::query()->findOrFail($campaign->organization_id));
            $originalUpdatedAt = $campaign->updated_at;
            $events = [];

            Event::listen('eloquent.updating: '.Campaign::class, function (Campaign $updating) use (&$events): void {
                $events[] = 'updating';
                $this->assertSame(CampaignStatus::Draft->value, $updating->getRawOriginal('status'));
                $this->assertSame(CampaignStatus::Active, $updating->status);
                $this->assertTrue($updating->isDirty('status'));
            });
            Event::listen('eloquent.updated: '.Campaign::class, function (Campaign $updated) use (&$events): void {
                $events[] = 'updated';
                $this->assertTrue($updated->wasChanged('status'));
                $this->assertArrayHasKey('status', $updated->getChanges());
                $this->assertArrayHasKey('updated_at', $updated->getChanges());
            });

            CarbonImmutable::setTestNow('2026-11-07 10:01:00');

            $this->assertTrue($campaign->update(['status' => CampaignStatus::Active]));
            $this->assertSame(['updating', 'updated'], $events);
            $this->assertNotEquals($originalUpdatedAt, $campaign->updated_at);
            $this->assertSame('2026-11-07 10:01:00', $campaign->updated_at?->format('Y-m-d H:i:s'));
            $this->assertSame(CampaignStatus::Active, $campaign->fresh()?->status);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_models_reject_direct_illegal_persisted_lifecycle_transitions(): void
    {
        $models = [
            [Campaign::factory()->create(['status' => CampaignStatus::Archived]), CampaignStatus::Active],
            [Keyword::factory()->create(['status' => KeywordStatus::Archived]), KeywordStatus::Active],
            [DiscoveredPost::factory()->create(['status' => DiscoveredPostStatus::Blocked]), DiscoveredPostStatus::New],
            [ReplyDraft::factory()->create(['status' => DraftStatus::Published]), DraftStatus::Edited],
            [ResponseMapping::factory()->create(['status' => MappingStatus::Archived]), MappingStatus::Active],
            [PromptVersion::factory()->create(['status' => PromptStatus::Retired]), PromptStatus::Active],
            [PublishAttempt::factory()->create(['status' => PublishAttemptStatus::Published]), PublishAttemptStatus::Publishing],
            [ScheduledJobRun::factory()->create(['status' => ScheduledJobStatus::Succeeded]), ScheduledJobStatus::Running],
            [KeywordSearchRun::factory()->create(['status' => ScheduledJobStatus::Succeeded]), ScheduledJobStatus::Running],
            [SystemAlert::factory()->create(['status' => AlertStatus::Resolved]), AlertStatus::Open],
            [ThreadsAccount::factory()->create(['status' => ThreadsAccountStatus::Revoked]), ThreadsAccountStatus::Connected],
        ];

        foreach ($models as [$model, $illegalStatus]) {
            app(OrganizationContext::class)->set(Organization::query()->findOrFail($model->organization_id));

            try {
                $model->update(['status' => $illegalStatus]);
                $this->fail(sprintf('%s accepted an illegal lifecycle transition.', $model::class));
            } catch (LogicException $exception) {
                $this->assertStringContainsString('Illegal status transition', $exception->getMessage());
            }
        }
    }

    public function test_lifecycle_models_reject_mass_query_status_updates(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Archived]);
        app(OrganizationContext::class)->set(Organization::query()->findOrFail($campaign->organization_id));

        try {
            Campaign::query()->whereKey($campaign)->update(['status' => CampaignStatus::Active->value]);
            $this->fail('A mass query update bypassed lifecycle transition enforcement.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('Mass lifecycle status writes are not supported', $exception->getMessage());
        }

        $this->assertSame(CampaignStatus::Archived, $campaign->fresh()->status);
    }

    public function test_lifecycle_models_reject_raw_expression_status_updates(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Archived]);
        app(OrganizationContext::class)->set(Organization::query()->findOrFail($campaign->organization_id));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Mass lifecycle status writes are not supported');

        Campaign::query()->whereKey($campaign)->update(['status' => DB::raw("'active'")]);
    }

    public function test_lifecycle_models_reject_status_upserts(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Archived]);
        app(OrganizationContext::class)->set(Organization::query()->findOrFail($campaign->organization_id));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Mass lifecycle status writes are not supported');

        Campaign::query()->upsert([
            [
                'id' => $campaign->id,
                'organization_id' => $campaign->organization_id,
                'name' => $campaign->name,
                'status' => CampaignStatus::Active->value,
            ],
        ], ['id'], ['status']);
    }

    public function test_lifecycle_models_reject_update_or_insert_status_writes(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Archived]);
        app(OrganizationContext::class)->set(Organization::query()->findOrFail($campaign->organization_id));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Mass lifecycle status writes are not supported');

        Campaign::query()->updateOrInsert(
            ['id' => $campaign->id],
            ['status' => CampaignStatus::Active->value],
        );
    }

    public function test_lifecycle_models_reject_update_from_status_writes(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Archived]);
        app(OrganizationContext::class)->set(Organization::query()->findOrFail($campaign->organization_id));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Mass lifecycle status writes are not supported');

        Campaign::query()->whereKey($campaign)->updateFrom(['status' => CampaignStatus::Active->value]);
    }

    public function test_lifecycle_models_allow_non_status_mass_query_updates(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Archived]);
        app(OrganizationContext::class)->set(Organization::query()->findOrFail($campaign->organization_id));

        $updated = Campaign::query()->whereKey($campaign)->update(['name' => 'Renamed safely']);

        $this->assertSame(1, $updated);
        $this->assertSame('Renamed safely', $campaign->fresh()->name);
        $this->assertSame(CampaignStatus::Archived, $campaign->fresh()->status);
    }
}
