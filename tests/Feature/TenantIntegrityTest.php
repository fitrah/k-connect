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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_organization_owned_relationship_has_a_composite_tenant_foreign_key(): void
    {
        $relationships = [
            'campaigns' => ['threads_account_id' => 'threads_accounts', 'default_response_mapping_id' => 'response_mappings'],
            'response_mappings' => ['campaign_id' => 'campaigns', 'fallback_response_mapping_id' => 'response_mappings'],
            'keywords' => ['campaign_id' => 'campaigns', 'response_mapping_id' => 'response_mappings'],
            'keyword_search_runs' => ['keyword_id' => 'keywords'],
            'discovered_posts' => ['threads_account_id' => 'threads_accounts'],
            'post_classifications' => ['discovered_post_id' => 'discovered_posts', 'prompt_version_id' => 'prompt_versions'],
            'mapping_versions' => ['response_mapping_id' => 'response_mappings', 'created_by' => 'users'],
            'prompt_versions' => ['created_by' => 'users'],
            'reply_drafts' => [
                'discovered_post_id' => 'discovered_posts',
                'post_classification_id' => 'post_classifications',
                'mapping_version_id' => 'mapping_versions',
                'prompt_version_id' => 'prompt_versions',
                'reviewed_by' => 'users',
            ],
            'reply_revisions' => ['reply_draft_id' => 'reply_drafts', 'created_by' => 'users'],
            'publish_attempts' => ['reply_draft_id' => 'reply_drafts', 'threads_account_id' => 'threads_accounts'],
            'quota_usages' => ['threads_account_id' => 'threads_accounts', 'campaign_id' => 'campaigns', 'keyword_id' => 'keywords'],
            'daily_metrics' => ['campaign_id' => 'campaigns', 'keyword_id' => 'keywords', 'response_mapping_id' => 'response_mappings', 'threads_account_id' => 'threads_accounts'],
            'system_alerts' => ['acknowledged_by' => 'users'],
            'audit_logs' => ['actor_id' => 'users'],
            'discovered_post_keyword' => [
                'discovered_post_id' => 'discovered_posts',
                'keyword_id' => 'keywords',
                'keyword_search_run_id' => 'keyword_search_runs',
            ],
        ];

        foreach ($relationships as $table => $foreignKeys) {
            $constraints = $this->foreignKeyColumns($table);

            foreach ($foreignKeys as $foreignColumn => $parentTable) {
                $this->assertTrue(
                    collect($constraints)->contains(fn (array $constraint): bool => $constraint === [
                        'organization_id' => [$parentTable, 'organization_id'],
                        $foreignColumn => [$parentTable, 'id'],
                    ]),
                    sprintf('%s.%s is not protected by a composite organization foreign key.', $table, $foreignColumn),
                );
            }
        }
    }

    public function test_default_factories_build_required_relationships_with_one_organization(): void
    {
        $models = [
            AuditLog::factory()->create(),
            Campaign::factory()->create(),
            DailyMetric::factory()->create(),
            Keyword::factory()->create(),
            KeywordSearchRun::factory()->create(),
            DiscoveredPost::factory()->create(),
            PostClassification::factory()->create(),
            MappingVersion::factory()->create(),
            PromptVersion::factory()->create(),
            ReplyDraft::factory()->create(),
            ReplyRevision::factory()->create(),
            PublishAttempt::factory()->create(),
            QuotaUsage::factory()->create(),
            ResponseMapping::factory()->create(),
            ScheduledJobRun::factory()->create(),
            SystemAlert::factory()->create(),
            ThreadsAccount::factory()->create(),
            User::factory()->create(),
        ];

        foreach ($models as $model) {
            app(OrganizationContext::class)->set(Organization::query()->findOrFail($model->organization_id));

            foreach ($model->getAttributes() as $column => $value) {
                if ($value === null || ! str_ends_with($column, '_id') || $column === 'organization_id') {
                    continue;
                }

                $relation = match ($column) {
                    'created_by' => 'createdBy',
                    'reviewed_by' => 'reviewedBy',
                    default => str($column)->beforeLast('_id')->camel()->toString(),
                };

                if (! method_exists($model, $relation)) {
                    continue;
                }

                $parent = $model->{$relation};

                if ($parent !== null && array_key_exists('organization_id', $parent->getAttributes())) {
                    $this->assertSame($model->organization_id, $parent->organization_id, sprintf('%s factory mixed organizations for %s.', $model::class, $relation));
                }
            }

            app(OrganizationContext::class)->clear();
        }
    }

    public function test_nullable_tenant_relationships_clear_only_the_foreign_id_when_the_parent_is_deleted(): void
    {
        $organization = Organization::factory()->create();
        app(OrganizationContext::class)->set($organization);

        $user = User::factory()->for($organization)->create();
        $mapping = ResponseMapping::factory()->for($organization)->create();
        $version = MappingVersion::factory()
            ->for($organization)
            ->for($mapping)
            ->for($user, 'createdBy')
            ->create();

        $user->delete();

        $version->refresh();

        $this->assertNull($version->created_by);
        $this->assertSame($organization->id, $version->organization_id);
    }

    /** @return array<int, array<string, array{0: string, 1: string}>> */
    private function foreignKeyColumns(string $table): array
    {
        if (DB::getDriverName() === 'sqlite') {
            $constraints = [];

            foreach (DB::select(sprintf('PRAGMA foreign_key_list("%s")', $table)) as $result) {
                $row = (array) $result;
                $constraints[(string) $row['id']][(string) $row['from']] = [
                    (string) $row['table'],
                    (string) $row['to'],
                ];
            }

            return array_values($constraints);
        }

        $rows = DB::select(<<<'SQL'
            SELECT tc.constraint_name, kcu.column_name, ccu.table_name AS foreign_table, ccu.column_name AS foreign_column
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
              ON tc.constraint_catalog = kcu.constraint_catalog
             AND tc.constraint_schema = kcu.constraint_schema
             AND tc.constraint_name = kcu.constraint_name
            JOIN information_schema.referential_constraints rc
              ON tc.constraint_catalog = rc.constraint_catalog
             AND tc.constraint_schema = rc.constraint_schema
             AND tc.constraint_name = rc.constraint_name
            JOIN information_schema.key_column_usage ccu
              ON rc.unique_constraint_catalog = ccu.constraint_catalog
             AND rc.unique_constraint_schema = ccu.constraint_schema
             AND rc.unique_constraint_name = ccu.constraint_name
             AND ccu.ordinal_position = kcu.position_in_unique_constraint
            WHERE tc.constraint_type = 'FOREIGN KEY'
              AND tc.table_schema = current_schema()
              AND tc.table_name = ?
            ORDER BY tc.constraint_name, kcu.ordinal_position
            SQL, [$table]);

        $constraints = [];

        foreach ($rows as $result) {
            $row = (array) $result;
            $constraints[(string) $row['constraint_name']][(string) $row['column_name']] = [
                (string) $row['foreign_table'],
                (string) $row['foreign_column'],
            ];
        }

        return array_values($constraints);
    }
}
