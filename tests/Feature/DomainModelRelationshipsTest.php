<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\DailyMetric;
use App\Models\DiscoveredPost;
use App\Models\Keyword;
use App\Models\KeywordSearchRun;
use App\Models\MappingVersion;
use App\Models\Organization;
use App\Models\PostClassification;
use App\Models\PromptVersion;
use App\Models\PublishAttempt;
use App\Models\QuotaUsage;
use App\Models\ReplyDraft;
use App\Models\ReplyRevision;
use App\Models\ResponseMapping;
use App\Models\ScheduledJobRun;
use App\Models\SystemAlert;
use App\Models\ThreadsAccount;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_factories_build_a_connected_foundation_domain_graph(): void
    {
        $organization = Organization::factory()->create();
        app(OrganizationContext::class)->set($organization);
        $user = User::factory()->for($organization)->create();
        $account = ThreadsAccount::factory()->for($organization)->create();
        $mapping = ResponseMapping::factory()->for($organization)->create();
        $campaign = Campaign::factory()
            ->for($organization)
            ->for($account, 'threadsAccount')
            ->for($mapping, 'defaultResponseMapping')
            ->create();
        $keyword = Keyword::factory()
            ->for($organization)
            ->for($campaign)
            ->for($mapping, 'responseMapping')
            ->create();
        $searchRun = KeywordSearchRun::factory()
            ->for($organization)
            ->for($keyword)
            ->create();
        $post = DiscoveredPost::factory()
            ->for($organization)
            ->for($account, 'threadsAccount')
            ->create();
        $post->keywords()->attach($keyword, [
            'keyword_search_run_id' => $searchRun->id,
            'first_discovered_at' => now(),
        ]);
        $classificationPrompt = PromptVersion::factory()->for($organization)->create();
        $classification = PostClassification::factory()
            ->for($organization)
            ->for($post, 'discoveredPost')
            ->for($classificationPrompt)
            ->create();
        $mappingVersion = MappingVersion::factory()
            ->for($organization)
            ->for($mapping, 'responseMapping')
            ->for($user, 'createdBy')
            ->create();
        $generationPrompt = PromptVersion::factory()->for($organization)->create();
        $draft = ReplyDraft::factory()
            ->for($organization)
            ->for($post, 'discoveredPost')
            ->for($classification, 'postClassification')
            ->for($mappingVersion)
            ->for($generationPrompt, 'promptVersion')
            ->create();
        $revision = ReplyRevision::factory()
            ->for($organization)
            ->for($draft, 'replyDraft')
            ->for($user, 'createdBy')
            ->create();
        $publishAttempt = PublishAttempt::factory()
            ->for($organization)
            ->for($draft, 'replyDraft')
            ->for($account, 'threadsAccount')
            ->create();
        $quotaUsage = QuotaUsage::factory()->for($organization)->for($account, 'threadsAccount')->create();
        $jobRun = ScheduledJobRun::factory()->for($organization)->create();
        $metric = DailyMetric::factory()->for($organization)->for($campaign)->create();
        $alert = SystemAlert::factory()->for($organization)->create();
        $audit = AuditLog::factory()->for($organization)->for($user, 'actor')->create();

        $this->assertTrue($user->organization->is($organization));
        $this->assertTrue($account->campaigns->contains($campaign));
        $this->assertTrue($campaign->keywords->contains($keyword));
        $this->assertTrue($keyword->searchRuns->contains($searchRun));
        $this->assertTrue($post->keywords->contains($keyword));
        $this->assertTrue($post->classifications->contains($classification));
        $this->assertTrue($mapping->versions->contains($mappingVersion));
        $this->assertTrue($draft->revisions->contains($revision));
        $this->assertTrue($draft->publishAttempts->contains($publishAttempt));
        $this->assertTrue($organization->quotaUsages->contains($quotaUsage));
        $this->assertTrue($organization->scheduledJobRuns->contains($jobRun));
        $this->assertTrue($organization->dailyMetrics->contains($metric));
        $this->assertTrue($organization->systemAlerts->contains($alert));
        $this->assertTrue($organization->auditLogs->contains($audit));
    }
}
