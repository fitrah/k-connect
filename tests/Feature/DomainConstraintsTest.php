<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\DiscoveredPost;
use App\Models\Keyword;
use App\Models\Organization;
use App\Models\ThreadsAccount;
use App\Support\OrganizationContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_threads_media_ids_are_rejected(): void
    {
        $post = DiscoveredPost::factory()->create(['threads_media_id' => 'threads-media-123']);
        $organization = Organization::query()->findOrFail($post->organization_id);
        app(OrganizationContext::class)->set($organization);

        $this->expectException(QueryException::class);

        DiscoveredPost::factory()
            ->for($organization)
            ->for($post->threadsAccount, 'threadsAccount')
            ->create(['threads_media_id' => 'threads-media-123']);
    }

    public function test_invalid_foreign_references_are_rejected(): void
    {
        $organization = Organization::factory()->create();

        $this->expectException(QueryException::class);

        Keyword::factory()->for($organization)->create(['campaign_id' => 999999]);
    }

    public function test_a_post_can_retain_multiple_keyword_attributions_without_duplicate_attribution(): void
    {
        $organization = Organization::factory()->create();
        app(OrganizationContext::class)->set($organization);
        $campaign = Campaign::factory()->for($organization)->create();
        $firstKeyword = Keyword::factory()->for($organization)->for($campaign)->create();
        $secondKeyword = Keyword::factory()->for($organization)->for($campaign)->create();
        $post = DiscoveredPost::factory()->for($organization)->create();

        $post->keywords()->attach($firstKeyword, ['first_discovered_at' => now()]);
        $post->keywords()->attach($secondKeyword, ['first_discovered_at' => now()]);

        $this->assertCount(2, $post->keywords);

        $this->expectException(QueryException::class);
        $post->keywords()->attach($firstKeyword, ['first_discovered_at' => now()]);
    }

    public function test_cross_organization_campaign_account_references_are_rejected(): void
    {
        $campaignOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $foreignAccount = ThreadsAccount::factory()->for($otherOrganization)->create();

        $this->expectException(QueryException::class);

        Campaign::factory()
            ->for($campaignOrganization)
            ->for($foreignAccount, 'threadsAccount')
            ->create();
    }
}
