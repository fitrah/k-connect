<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class FoundationMigrationTest extends TestCase
{
    private string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $databasePath = tempnam(storage_path('framework/testing'), 'foundation-migration-');

        if ($databasePath === false) {
            $this->fail('Unable to create a temporary migration database.');
        }

        $this->databasePath = $databasePath;

        config()->set('database.connections.migration_qa', [
            'driver' => 'sqlite',
            'database' => $this->databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
            'busy_timeout' => 5000,
            'journal_mode' => null,
            'synchronous' => null,
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('migration_qa');
        @unlink($this->databasePath);

        parent::tearDown();
    }

    public function test_foundation_migrations_rollback_and_reapply_safely(): void
    {
        $this->assertSame(0, Artisan::call('migrate:fresh', ['--database' => 'migration_qa', '--force' => true]));
        $this->assertTrue(Schema::connection('migration_qa')->hasColumn('campaigns', 'threads_account_id'));

        $this->assertSame(0, Artisan::call('migrate:rollback', ['--database' => 'migration_qa', '--force' => true]));
        $this->assertFalse(Schema::connection('migration_qa')->hasTable('campaigns'));

        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'migration_qa', '--force' => true]));
        $this->assertTrue(Schema::connection('migration_qa')->hasColumn('campaigns', 'threads_account_id'));
    }

    public function test_reapplying_migrations_is_idempotent(): void
    {
        $this->assertSame(0, Artisan::call('migrate:fresh', ['--database' => 'migration_qa', '--force' => true]));
        $migrationCount = DB::connection('migration_qa')->table('migrations')->count();

        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'migration_qa', '--force' => true]));
        $this->assertSame($migrationCount, DB::connection('migration_qa')->table('migrations')->count());
    }

    public function test_user_organization_ownership_is_database_required(): void
    {
        $this->assertSame(0, Artisan::call('migrate:fresh', ['--database' => 'migration_qa', '--force' => true]));

        $organizationColumn = collect(Schema::connection('migration_qa')->getColumns('users'))
            ->firstWhere('name', 'organization_id');

        $this->assertIsArray($organizationColumn);
        $this->assertFalse($organizationColumn['nullable']);
    }

    public function test_postgresql_prior_schema_preserves_sentinel_data_through_upgrade_rollback_and_reapply(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL migration evidence requires DB_CONNECTION=pgsql.');
        }

        $schema = 'migration_qa_'.Str::lower(Str::random(12));
        $connection = 'migration_qa_pgsql';
        $finalMigration = database_path('migrations/2026_11_06_175112_add_deferred_foundation_foreign_keys.php');
        $priorMigrations = array_values(array_filter(
            glob(database_path('migrations/*.php')) ?: [],
            fn (string $migration): bool => $migration !== $finalMigration,
        ));

        DB::statement(sprintf('CREATE SCHEMA "%s"', $schema));

        try {
            $configuration = config('database.connections.pgsql');
            $this->assertIsArray($configuration);
            $configuration['search_path'] = $schema;
            config()->set("database.connections.{$connection}", $configuration);
            DB::purge($connection);

            $this->assertSame(0, Artisan::call('migrate', [
                '--database' => $connection,
                '--path' => $priorMigrations,
                '--realpath' => true,
                '--force' => true,
            ]));

            $organizationId = DB::connection($connection)->table('organizations')->insertGetId([
                'name' => 'Migration sentinel organization',
                'slug' => 'migration-sentinel',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $campaignId = DB::connection($connection)->table('campaigns')->insertGetId([
                'organization_id' => $organizationId,
                'name' => 'Migration sentinel campaign',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertSame(0, Artisan::call('migrate', [
                '--database' => $connection,
                '--path' => $finalMigration,
                '--realpath' => true,
                '--force' => true,
            ]));
            $this->assertTrue(Schema::connection($connection)->hasColumn('campaigns', 'threads_account_id'));

            $this->assertSame(0, Artisan::call('migrate:rollback', [
                '--database' => $connection,
                '--step' => 1,
                '--force' => true,
            ]));
            $this->assertFalse(Schema::connection($connection)->hasColumn('campaigns', 'threads_account_id'));
            $this->assertSame('Migration sentinel campaign', DB::connection($connection)->table('campaigns')->where('id', $campaignId)->value('name'));

            $this->assertSame(0, Artisan::call('migrate', [
                '--database' => $connection,
                '--path' => $finalMigration,
                '--realpath' => true,
                '--force' => true,
            ]));
            $this->assertTrue(Schema::connection($connection)->hasColumn('campaigns', 'threads_account_id'));
            $this->assertSame('Migration sentinel campaign', DB::connection($connection)->table('campaigns')->where('id', $campaignId)->value('name'));
        } finally {
            DB::purge($connection);
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $schema));
        }
    }
}
