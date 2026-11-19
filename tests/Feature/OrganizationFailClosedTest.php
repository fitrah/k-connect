<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Support\OrganizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class OrganizationFailClosedTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_owned_reads_and_writes_fail_closed_without_context(): void
    {
        $organization = Organization::factory()->create();
        app(OrganizationContext::class)->runAsSystem(
            fn () => Campaign::factory()->for($organization)->create(),
        );

        foreach ([
            fn () => Campaign::query()->count(),
            fn () => Campaign::query()->create(['name' => 'No context', 'status' => 'draft']),
            fn () => Campaign::query()->update(['name' => 'No context']),
            fn () => Campaign::query()->delete(),
        ] as $operation) {
            try {
                $operation();
                $this->fail('An organization-owned operation succeeded without context.');
            } catch (LogicException $exception) {
                $this->assertStringContainsString('Organization context is required', $exception->getMessage());
            }
        }
    }

    public function test_instance_deletes_reject_a_different_active_organization(): void
    {
        $firstOrganization = Organization::factory()->create();
        $secondOrganization = Organization::factory()->create();
        $campaign = app(OrganizationContext::class)->runAsSystem(
            fn () => Campaign::factory()->for($firstOrganization)->create(),
        );

        app(OrganizationContext::class)->set($secondOrganization);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('conflicts with the active organization');

        $campaign->delete();
    }

    public function test_removing_global_scopes_cannot_widen_organization_owned_reads(): void
    {
        $activeOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $activeCampaign = Campaign::factory()->for($activeOrganization)->create();
        Campaign::factory()->for($otherOrganization)->create();
        app(OrganizationContext::class)->set($activeOrganization);

        $queries = [
            Campaign::withoutGlobalScope(OrganizationScope::class),
            Campaign::withoutGlobalScopes(),
            (new Campaign)->newQueryWithoutScopes(),
        ];

        foreach ($queries as $query) {
            $this->assertSame([$activeCampaign->id], $query->pluck('id')->all());
        }
    }

    public function test_prebuilt_queries_fail_closed_after_the_organization_context_changes(): void
    {
        $firstOrganization = Organization::factory()->create();
        $secondOrganization = Organization::factory()->create();
        Campaign::factory()->for($firstOrganization)->create();
        app(OrganizationContext::class)->set($firstOrganization);

        $readQuery = Campaign::query();
        $writeQuery = Campaign::query();
        app(OrganizationContext::class)->set($secondOrganization);

        foreach ([
            fn () => $readQuery->count(),
            fn () => $writeQuery->update(['name' => 'Stale boundary']),
        ] as $operation) {
            try {
                $operation();
                $this->fail('A query retained access after the organization context changed.');
            } catch (LogicException $exception) {
                $this->assertStringContainsString('Organization context changed', $exception->getMessage());
            }
        }
    }

    public function test_system_bypass_requires_explicit_organization_ownership_for_writes(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('System organization writes must supply organization_id explicitly.');

        app(OrganizationContext::class)->runAsSystem(
            fn () => Campaign::query()->create(['name' => 'Missing owner', 'status' => 'draft']),
        );
    }

    public function test_system_bypass_rejects_instance_updates_without_an_explicit_organization_constraint(): void
    {
        $firstOrganization = Organization::factory()->create();
        $secondOrganization = Organization::factory()->create();
        $campaign = app(OrganizationContext::class)->runAsSystem(
            fn () => Campaign::factory()->for($firstOrganization)->create(),
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('must constrain organization_id explicitly');

        app(OrganizationContext::class)->runAsSystem(
            fn () => $campaign->update(['organization_id' => $secondOrganization->id]),
        );
    }

    public function test_system_bypass_is_callback_scoped_and_restored_after_success_or_exception(): void
    {
        $organization = Organization::factory()->create();
        $context = app(OrganizationContext::class);

        $campaign = $context->runAsSystem(fn () => Campaign::factory()->for($organization)->create());
        $this->assertTrue($campaign->organization->is($organization));
        $this->assertFalse($context->isSystemBypassed());

        try {
            $context->runAsSystem(function (): never {
                throw new RuntimeException('stop');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('stop', $exception->getMessage());
        }

        $this->assertFalse($context->isSystemBypassed());

        $this->expectException(LogicException::class);
        Campaign::query()->count();
    }
}
