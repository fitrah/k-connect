<?php

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Models\Builders\LifecycleEloquentBuilder;
use App\Models\Builders\OrganizationEloquentBuilder;
use App\Models\Campaign;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\EnforcesStatusTransitions;
use App\Models\Organization;
use App\Support\OrganizationContext;
use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Tests\TestCase;

class LifecycleWriteArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_does_not_bypass_protected_eloquent_builders(): void
    {
        $files = new RegexIterator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())),
            '/\.php$/',
        );

        $violations = [];
        $unsupportedLowerLevelWrites = '/DB::(?:table|statement|unprepared|insert|update|delete)|->(?:toBase|getQuery|forModelPersistence|withoutGlobalScopes?|saveQuietly|updateQuietly|deleteQuietly|forceDeleteQuietly)\(|::withoutEvents\(/';

        foreach ($files as $file) {
            if (in_array($file->getPathname(), [
                app_path('Models/Builders/LifecycleEloquentBuilder.php'),
                app_path('Models/Builders/OrganizationEloquentBuilder.php'),
                app_path('Models/Concerns/EnforcesStatusTransitions.php'),
            ], true)) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            if ($contents !== false && preg_match($unsupportedLowerLevelWrites, $contents) === 1) {
                $violations[] = $file->getPathname();
            }
        }

        $this->assertSame([], $violations, 'Organization-owned and lifecycle-managed tables must be written through Eloquent models, not lower-level query or raw SQL APIs.');
    }

    public function test_every_organization_owned_model_uses_the_protected_builder(): void
    {
        $files = new RegexIterator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path('Models'))),
            '/\.php$/',
        );
        $organizationOwnedModels = 0;

        foreach ($files as $file) {
            $relativePath = str_replace([app_path().DIRECTORY_SEPARATOR, '.php'], '', $file->getPathname());
            $class = 'App\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

            if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
                continue;
            }

            $traits = class_uses_recursive($class);

            if (! in_array(BelongsToOrganization::class, $traits, true)) {
                continue;
            }

            $organizationOwnedModels++;
            $builder = $class::query();

            $this->assertInstanceOf(OrganizationEloquentBuilder::class, $builder, $class);

            if (in_array(EnforcesStatusTransitions::class, $traits, true)) {
                $this->assertInstanceOf(LifecycleEloquentBuilder::class, $builder, $class);
            }
        }

        $this->assertSame(18, $organizationOwnedModels);
    }

    public function test_lifecycle_builder_exposes_no_reusable_model_persistence_bypass(): void
    {
        $organization = Organization::factory()->create();
        $first = Campaign::factory()->for($organization)->create(['status' => CampaignStatus::Archived]);
        $second = Campaign::factory()->for($organization)->create(['status' => CampaignStatus::Archived]);
        app(OrganizationContext::class)->set($organization);

        $builder = Campaign::query()->whereKey($first);
        $clonedBuilder = clone $builder;

        $this->assertFalse(method_exists($builder, 'forModelPersistence'));

        foreach ([$builder, $builder, $clonedBuilder] as $candidate) {
            try {
                $candidate->__call('forModelPersistence', []);
                $this->fail('A lifecycle builder exposed a reusable status-write bypass.');
            } catch (BadMethodCallException) {
                // Expected: no public builder capability may disable lifecycle enforcement.
            }
        }

        $this->assertSame(CampaignStatus::Archived, $first->fresh()?->status);
        $this->assertSame(CampaignStatus::Archived, $second->fresh()?->status);
    }
}
