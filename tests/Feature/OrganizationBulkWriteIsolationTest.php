<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\DailyMetric;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Support\OrganizationContext;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class OrganizationBulkWriteIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_insert_stamps_every_row_with_the_active_organization(): void
    {
        $organization = Organization::factory()->create();
        app(OrganizationContext::class)->set($organization);

        $inserted = DailyMetric::query()->insert([
            ['metric_date' => '2026-11-06', 'searches' => 2],
            ['metric_date' => '2026-11-07', 'searches' => 3],
        ]);

        $this->assertTrue($inserted);
        $this->assertDatabaseHas('daily_metrics', [
            'organization_id' => $organization->id,
            'metric_date' => '2026-11-06',
            'searches' => 2,
        ]);
        $this->assertDatabaseHas('daily_metrics', [
            'organization_id' => $organization->id,
            'metric_date' => '2026-11-07',
            'searches' => 3,
        ]);
    }

    public function test_insert_or_ignore_stamps_rows_with_the_active_organization(): void
    {
        $organization = Organization::factory()->create();
        app(OrganizationContext::class)->set($organization);

        $inserted = DailyMetric::query()->insertOrIgnore([
            ['metric_date' => '2026-11-07', 'searches' => 4],
        ]);

        $this->assertSame(1, $inserted);
        $this->assertDatabaseHas('daily_metrics', [
            'organization_id' => $organization->id,
            'metric_date' => '2026-11-07',
            'searches' => 4,
        ]);
    }

    public function test_empty_bulk_insert_inputs_remain_no_ops(): void
    {
        $organization = Organization::factory()->create();
        app(OrganizationContext::class)->set($organization);

        $inserted = DailyMetric::query()->insert([]);
        $ignored = DailyMetric::query()->insertOrIgnore([]);

        $this->assertTrue($inserted);
        $this->assertSame(0, $ignored);
        $this->assertDatabaseCount('daily_metrics', 0);
    }

    public function test_bulk_inserts_reject_rows_owned_by_another_organization(): void
    {
        $activeOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        app(OrganizationContext::class)->set($activeOrganization);

        foreach ([
            fn () => DailyMetric::query()->insert([
                'organization_id' => $otherOrganization->id,
                'metric_date' => '2026-11-07',
            ]),
            fn () => DailyMetric::query()->insertOrIgnore([
                'organization_id' => $otherOrganization->id,
                'metric_date' => '2026-11-07',
            ]),
        ] as $operation) {
            try {
                $operation();
                $this->fail('A bulk insert accepted a conflicting organization_id.');
            } catch (LogicException $exception) {
                $this->assertStringContainsString('conflicts with the active organization', $exception->getMessage());
            }
        }

        $this->assertDatabaseMissing('daily_metrics', [
            'organization_id' => $otherOrganization->id,
            'metric_date' => '2026-11-07',
        ]);
    }

    public function test_insert_using_variants_are_rejected_when_row_ownership_cannot_be_validated(): void
    {
        $organization = Organization::factory()->create();
        app(OrganizationContext::class)->set($organization);

        $this->assertLogicException(
            fn () => DailyMetric::query()->insertUsing(
                ['organization_id', 'metric_date'],
                Organization::query()->selectRaw('id, ?', ['2026-11-07']),
            ),
            'cannot validate organization ownership',
        );
        $this->assertLogicException(
            fn () => DailyMetric::query()->insertOrIgnoreUsing(
                ['organization_id', 'metric_date'],
                Organization::query()->selectRaw('id, ?', ['2026-11-07']),
            ),
            'cannot validate organization ownership',
        );
    }

    public function test_bulk_organization_id_updates_are_rejected_for_lifecycle_and_standard_models(): void
    {
        $activeOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $campaign = Campaign::factory()->for($activeOrganization)->create();
        $metric = DailyMetric::factory()->for($activeOrganization)->create(['campaign_id' => $campaign->id]);
        app(OrganizationContext::class)->set($activeOrganization);

        foreach ([
            fn () => Campaign::query()->whereKey($campaign)->update(['organization_id' => $otherOrganization->id]),
            fn () => DailyMetric::query()->whereKey($metric)->update(['organization_id' => $otherOrganization->id]),
        ] as $operation) {
            try {
                $operation();
                $this->fail('A bulk update reassigned organization ownership.');
            } catch (LogicException $exception) {
                $this->assertStringContainsString('organization_id reassignment', $exception->getMessage());
            }
        }

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'organization_id' => $activeOrganization->id,
        ]);
        $this->assertDatabaseHas('daily_metrics', [
            'id' => $metric->id,
            'organization_id' => $activeOrganization->id,
        ]);
    }

    public function test_upsert_requires_organization_id_in_the_conflict_key(): void
    {
        $activeOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $foreignMetric = DailyMetric::factory()->for($otherOrganization)->create([
            'campaign_id' => null,
            'metric_date' => '2026-11-06',
        ]);
        app(OrganizationContext::class)->set($activeOrganization);

        try {
            DailyMetric::query()->upsert([
                [
                    'id' => $foreignMetric->id,
                    'organization_id' => $activeOrganization->id,
                    'metric_date' => '2026-11-07',
                ],
            ], ['id'], ['metric_date']);
            $this->fail('An upsert used a conflict key that was not organization-scoped.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('organization_id in its unique key', $exception->getMessage());
        }

        $unchangedMetric = app(OrganizationContext::class)->runAsSystem(
            fn () => DailyMetric::query()->forSystemOrganization($otherOrganization)->findOrFail($foreignMetric->id),
        );

        $this->assertSame($otherOrganization->id, $unchangedMetric->organization_id);
        $this->assertSame('2026-11-06', CarbonImmutable::parse((string) $unchangedMetric->metric_date)->toDateString());
    }

    public function test_update_or_insert_scopes_lookups_and_inserts_to_the_active_organization(): void
    {
        $activeOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $foreignMetric = DailyMetric::factory()->for($otherOrganization)->create([
            'campaign_id' => null,
            'metric_date' => '2026-11-07',
            'searches' => 5,
        ]);
        app(OrganizationContext::class)->set($activeOrganization);

        $succeeded = DailyMetric::query()->updateOrInsert(
            ['metric_date' => '2026-11-07'],
            ['searches' => 9],
        );

        $this->assertTrue($succeeded);
        $this->assertDatabaseHas('daily_metrics', [
            'id' => $foreignMetric->id,
            'organization_id' => $otherOrganization->id,
            'searches' => 5,
        ]);
        $this->assertDatabaseHas('daily_metrics', [
            'organization_id' => $activeOrganization->id,
            'metric_date' => '2026-11-07',
            'searches' => 9,
        ]);
    }

    public function test_increment_and_decrement_extras_cannot_reassign_organization_ownership(): void
    {
        $activeOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $campaign = Campaign::factory()->for($activeOrganization)->create([
            'hourly_reply_limit' => 10,
            'daily_reply_limit' => 20,
        ]);
        app(OrganizationContext::class)->set($activeOrganization);

        foreach ([
            fn () => Campaign::query()->whereKey($campaign)->increment(
                'hourly_reply_limit',
                1,
                ['organization_id' => $otherOrganization->id],
            ),
            fn () => Campaign::query()->whereKey($campaign)->decrement(
                'daily_reply_limit',
                1,
                ['organization_id' => $otherOrganization->id],
            ),
        ] as $operation) {
            try {
                $operation();
                $this->fail('An arithmetic bulk update reassigned organization ownership.');
            } catch (LogicException $exception) {
                $this->assertStringContainsString('organization_id reassignment', $exception->getMessage());
            }
        }

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'organization_id' => $activeOrganization->id,
            'hourly_reply_limit' => 10,
            'daily_reply_limit' => 20,
        ]);
    }

    public function test_bulk_write_apis_fail_closed_without_organization_context(): void
    {
        $organization = Organization::factory()->create();
        $metric = DailyMetric::factory()->for($organization)->create(['campaign_id' => null]);
        app(OrganizationContext::class)->clear();

        foreach ([
            fn () => DailyMetric::query()->insert(['metric_date' => '2026-11-08']),
            fn () => DailyMetric::query()->insertOrIgnore(['metric_date' => '2026-11-08']),
            fn () => DailyMetric::query()->whereKey($metric)->update(['searches' => 7]),
            fn () => DailyMetric::query()->upsert([
                ['id' => $metric->id, 'organization_id' => $organization->id, 'metric_date' => '2026-11-08'],
            ], ['organization_id', 'id'], ['metric_date']),
            fn () => DailyMetric::query()->updateOrInsert(['metric_date' => '2026-11-08'], ['searches' => 7]),
            fn () => DailyMetric::query()->whereKey($metric)->increment('searches'),
            fn () => DailyMetric::query()->whereKey($metric)->decrement('searches'),
        ] as $operation) {
            try {
                $operation();
                $this->fail('An organization-owned bulk write succeeded without context.');
            } catch (LogicException $exception) {
                $this->assertStringContainsString('Organization context is required', $exception->getMessage());
            }
        }
    }

    public function test_removing_global_scopes_cannot_widen_organization_owned_bulk_writes(): void
    {
        $activeOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $activeMetrics = DailyMetric::factory()->count(4)->for($activeOrganization)->create([
            'campaign_id' => null,
            'searches' => 1,
        ]);
        $foreignMetrics = DailyMetric::factory()->count(4)->for($otherOrganization)->create([
            'campaign_id' => null,
            'searches' => 2,
        ]);
        app(OrganizationContext::class)->set($activeOrganization);

        $updated = DailyMetric::withoutGlobalScope(OrganizationScope::class)
            ->whereKey($activeMetrics[0]->id)
            ->orWhereKey($foreignMetrics[0]->id)
            ->update(['searches' => 10]);
        $incremented = DailyMetric::withoutGlobalScopes()
            ->whereKey($activeMetrics[1]->id)
            ->orWhereKey($foreignMetrics[1]->id)
            ->increment('searches', 3);
        $deleted = (new DailyMetric)->newQueryWithoutScopes()
            ->whereKey($activeMetrics[2]->id)
            ->orWhereKey($foreignMetrics[2]->id)
            ->delete();
        $forceDeleted = DailyMetric::withoutGlobalScope(OrganizationScope::class)
            ->whereKey($activeMetrics[3]->id)
            ->orWhereKey($foreignMetrics[3]->id)
            ->forceDelete();

        $this->assertSame(1, $updated);
        $this->assertSame(1, $incremented);
        $this->assertSame(1, $deleted);
        $this->assertSame(1, $forceDeleted);
        $this->assertDatabaseHas('daily_metrics', ['id' => $foreignMetrics[0]->id, 'searches' => 2]);
        $this->assertDatabaseHas('daily_metrics', ['id' => $foreignMetrics[1]->id, 'searches' => 2]);
        $this->assertDatabaseHas('daily_metrics', ['id' => $foreignMetrics[2]->id]);
        $this->assertDatabaseHas('daily_metrics', ['id' => $foreignMetrics[3]->id]);
    }

    public function test_trusted_system_bulk_inserts_require_explicit_organization_id(): void
    {
        foreach ([
            fn () => DailyMetric::query()->insert(['metric_date' => '2026-11-07']),
            fn () => DailyMetric::query()->insert([
                'organization_id' => null,
                'metric_date' => '2026-11-07',
            ]),
            fn () => DailyMetric::query()->insertOrIgnore(['metric_date' => '2026-11-07']),
            fn () => DailyMetric::query()->upsert([
                ['id' => 123, 'metric_date' => '2026-11-07'],
            ], ['organization_id', 'id'], ['metric_date']),
            fn () => DailyMetric::query()->updateOrInsert(
                ['metric_date' => '2026-11-07'],
                ['searches' => 1],
            ),
        ] as $operation) {
            try {
                app(OrganizationContext::class)->runAsSystem($operation);
                $this->fail('A system bulk write omitted organization_id.');
            } catch (LogicException $exception) {
                $this->assertStringContainsString('must supply organization_id explicitly', $exception->getMessage());
            }
        }
    }

    public function test_trusted_system_bulk_inserts_accept_explicit_organization_id(): void
    {
        $organization = Organization::factory()->create();

        $inserted = app(OrganizationContext::class)->runAsSystem(
            fn () => DailyMetric::query()->insert([
                'organization_id' => $organization->id,
                'metric_date' => '2026-11-07',
                'searches' => 6,
            ]),
        );

        $this->assertTrue($inserted);
        $this->assertDatabaseHas('daily_metrics', [
            'organization_id' => $organization->id,
            'metric_date' => '2026-11-07',
            'searches' => 6,
        ]);
    }

    public function test_trusted_system_bulk_updates_require_an_explicit_organization_constraint(): void
    {
        $firstOrganization = Organization::factory()->create();
        $secondOrganization = Organization::factory()->create();
        $firstMetric = DailyMetric::factory()->for($firstOrganization)->create(['campaign_id' => null, 'searches' => 1]);
        $secondMetric = DailyMetric::factory()->for($secondOrganization)->create(['campaign_id' => null, 'searches' => 2]);

        try {
            app(OrganizationContext::class)->runAsSystem(
                fn () => DailyMetric::query()->update(['searches' => 99]),
            );
            $this->fail('A system bulk update omitted an organization_id constraint.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('must constrain organization_id explicitly', $exception->getMessage());
        }

        $updated = app(OrganizationContext::class)->runAsSystem(
            fn () => DailyMetric::query()
                ->forSystemOrganization($firstOrganization)
                ->update(['searches' => 7]),
        );

        $this->assertSame(1, $updated);
        $this->assertDatabaseHas('daily_metrics', ['id' => $firstMetric->id, 'searches' => 7]);
        $this->assertDatabaseHas('daily_metrics', ['id' => $secondMetric->id, 'searches' => 2]);
    }

    public function test_system_organization_boundary_cannot_be_widened_by_adversarial_predicates(): void
    {
        $firstOrganization = Organization::factory()->create();
        $secondOrganization = Organization::factory()->create();
        $firstMetric = DailyMetric::factory()->for($firstOrganization)->create(['campaign_id' => null, 'searches' => 1]);
        $secondMetric = DailyMetric::factory()->for($secondOrganization)->create(['campaign_id' => null, 'searches' => 2]);

        $updated = app(OrganizationContext::class)->runAsSystem(
            fn () => DailyMetric::query()
                ->forSystemOrganization($firstOrganization)
                ->where(function ($query) use ($firstMetric): void {
                    $query->whereKey($firstMetric->id)->orWhereRaw('1 = 1');
                })
                ->orWhereNull('campaign_id')
                ->orWhereColumn('organization_id', 'organization_id')
                ->orWhereRaw('1 = 1')
                ->update(['searches' => 7]),
        );

        $this->assertSame(1, $updated);
        $this->assertDatabaseHas('daily_metrics', ['id' => $firstMetric->id, 'searches' => 7]);
        $this->assertDatabaseHas('daily_metrics', ['id' => $secondMetric->id, 'searches' => 2]);
    }

    public function test_trusted_system_bulk_updates_reject_ambiguous_organization_constraints(): void
    {
        $firstOrganization = Organization::factory()->create();
        $secondOrganization = Organization::factory()->create();
        $firstMetric = DailyMetric::factory()->for($firstOrganization)->create(['campaign_id' => null, 'searches' => 1]);
        $secondMetric = DailyMetric::factory()->for($secondOrganization)->create(['campaign_id' => null, 'searches' => 2]);

        foreach ([
            fn () => DailyMetric::query()
                ->where('organization_id', $firstOrganization->id)
                ->orWhere('organization_id', $secondOrganization->id)
                ->update(['searches' => 99]),
            fn () => DailyMetric::query()
                ->where('organization_id', DB::raw('organization_id'))
                ->update(['searches' => 99]),
        ] as $operation) {
            try {
                app(OrganizationContext::class)->runAsSystem($operation);
                $this->fail('A system bulk update accepted an ambiguous organization_id constraint.');
            } catch (LogicException $exception) {
                $this->assertStringContainsString('must constrain organization_id explicitly', $exception->getMessage());
            }
        }

        $this->assertDatabaseHas('daily_metrics', ['id' => $firstMetric->id, 'searches' => 1]);
        $this->assertDatabaseHas('daily_metrics', ['id' => $secondMetric->id, 'searches' => 2]);
    }

    public function test_force_delete_remains_organization_scoped_and_truncate_is_rejected(): void
    {
        $activeOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $activeMetric = DailyMetric::factory()->for($activeOrganization)->create(['campaign_id' => null]);
        $foreignMetric = DailyMetric::factory()->for($otherOrganization)->create(['campaign_id' => null]);
        app(OrganizationContext::class)->set($activeOrganization);

        $deleted = DailyMetric::query()->forceDelete();

        $this->assertSame(1, $deleted);
        $this->assertDatabaseMissing('daily_metrics', ['id' => $activeMetric->id]);
        $this->assertDatabaseHas('daily_metrics', ['id' => $foreignMetric->id]);

        $this->assertLogicException(
            fn () => DailyMetric::query()->truncate(),
            'truncate',
        );
    }

    public function test_same_organization_non_ownership_bulk_updates_remain_permitted(): void
    {
        $organization = Organization::factory()->create();
        $campaign = Campaign::factory()->for($organization)->create(['hourly_reply_limit' => 10]);
        $metric = DailyMetric::factory()->for($organization)->create([
            'campaign_id' => $campaign->id,
            'searches' => 2,
        ]);
        app(OrganizationContext::class)->set($organization);

        $renamed = Campaign::query()->whereKey($campaign)->update(['name' => 'Scoped campaign']);
        $incremented = DailyMetric::query()->whereKey($metric)->increment('searches', 3);
        $decremented = Campaign::query()->whereKey($campaign)->decrement('hourly_reply_limit', 2);

        $this->assertSame(1, $renamed);
        $this->assertSame(1, $incremented);
        $this->assertSame(1, $decremented);
        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'organization_id' => $organization->id,
            'name' => 'Scoped campaign',
            'hourly_reply_limit' => 8,
        ]);
        $this->assertDatabaseHas('daily_metrics', [
            'id' => $metric->id,
            'organization_id' => $organization->id,
            'searches' => 5,
        ]);
    }

    private function assertLogicException(Closure $operation, string $message): void
    {
        try {
            $operation();
            $this->fail('An organization-owned bulk write bypassed its guard.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());
        }
    }
}
