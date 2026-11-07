<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Organization;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class OrganizationContextTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(OrganizationContext::class)->clear();

        parent::tearDown();
    }

    public function test_organization_owned_models_are_assigned_the_active_organization(): void
    {
        $organization = Organization::factory()->create();
        app(OrganizationContext::class)->set($organization);

        $campaign = Campaign::query()->create([
            'name' => 'Assigned campaign',
            'status' => 'draft',
        ]);

        $this->assertTrue($campaign->organization->is($organization));
    }

    public function test_explicit_conflicting_organization_assignment_is_rejected(): void
    {
        $activeOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        app(OrganizationContext::class)->set($activeOrganization);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('conflicts with the active organization');

        Campaign::factory()->create(['organization_id' => $otherOrganization->id]);
    }

    public function test_updating_a_record_to_a_conflicting_organization_is_rejected(): void
    {
        $activeOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $campaign = Campaign::factory()->for($activeOrganization)->create();
        app(OrganizationContext::class)->set($activeOrganization);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('conflicts with the active organization');

        $campaign->update(['organization_id' => $otherOrganization->id]);
    }

    public function test_organization_scoped_queries_exclude_other_organizations(): void
    {
        $activeOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        Campaign::factory()->for($activeOrganization)->create(['name' => 'Visible campaign']);
        Campaign::factory()->for($otherOrganization)->create(['name' => 'Hidden campaign']);

        app(OrganizationContext::class)->set($activeOrganization);

        $this->assertSame(['Visible campaign'], Campaign::query()->pluck('name')->all());
    }

    public function test_context_can_be_cleared_and_scoped_instances_do_not_leak(): void
    {
        $organization = Organization::factory()->create();
        $context = app(OrganizationContext::class);
        $context->set($organization);

        $this->assertTrue($context->organization()?->is($organization));

        $context->clear();
        $this->assertNull($context->organization());

        $context->set($organization);
        app()->forgetScopedInstances();

        $this->assertNull(app(OrganizationContext::class)->organization());
    }
}
