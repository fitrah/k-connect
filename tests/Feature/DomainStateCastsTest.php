<?php

namespace Tests\Feature;

use App\Enums\AlertStatus;
use App\Enums\CampaignStatus;
use App\Enums\DiscoveredPostStatus;
use App\Enums\DraftStatus;
use App\Enums\KeywordStatus;
use App\Enums\PublishAttemptStatus;
use App\Enums\ScheduledJobStatus;
use App\Models\Campaign;
use App\Models\DiscoveredPost;
use App\Models\Keyword;
use App\Models\PublishAttempt;
use App\Models\ReplyDraft;
use App\Models\ScheduledJobRun;
use App\Models\SystemAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ValueError;

class DomainStateCastsTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_lifecycle_states_are_cast_to_enums(): void
    {
        $campaign = Campaign::factory()->create(['status' => CampaignStatus::Active]);
        $keyword = Keyword::factory()->create(['status' => KeywordStatus::Paused]);
        $post = DiscoveredPost::factory()->create(['status' => DiscoveredPostStatus::AwaitingClassification]);
        $draft = ReplyDraft::factory()->create(['status' => DraftStatus::Approved]);
        $attempt = PublishAttempt::factory()->create(['status' => PublishAttemptStatus::CreatingContainer]);
        $jobRun = ScheduledJobRun::factory()->create(['status' => ScheduledJobStatus::Running]);
        $alert = SystemAlert::factory()->create(['status' => AlertStatus::Acknowledged]);

        $this->assertSame(CampaignStatus::Active, $campaign->status);
        $this->assertSame(KeywordStatus::Paused, $keyword->status);
        $this->assertSame(DiscoveredPostStatus::AwaitingClassification, $post->status);
        $this->assertSame(DraftStatus::Approved, $draft->status);
        $this->assertSame(PublishAttemptStatus::CreatingContainer, $attempt->status);
        $this->assertSame(ScheduledJobStatus::Running, $jobRun->status);
        $this->assertSame(AlertStatus::Acknowledged, $alert->status);
    }

    public function test_campaign_state_transitions_are_explicitly_permitted(): void
    {
        $this->assertTrue(CampaignStatus::Draft->canTransitionTo(CampaignStatus::Active));
        $this->assertTrue(CampaignStatus::Active->canTransitionTo(CampaignStatus::Paused));
        $this->assertFalse(CampaignStatus::Archived->canTransitionTo(CampaignStatus::Active));
    }

    public function test_model_rejects_unknown_lifecycle_states(): void
    {
        $campaign = Campaign::factory()->create();

        $this->expectException(ValueError::class);

        $campaign->setAttribute('status', 'unknown');
        $campaign->save();
    }
}
