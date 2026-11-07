# K-Connect

K-Connect is a Laravel and Livewire application foundation for Threads keyword monitoring, AI-assisted reply preparation, human approval, operational reporting, and auditability.

KC-2 establishes the organization-owned persistence model only. Threads API calls, LLM execution, authentication UI, publishing workflows, and production deployment are intentionally outside this story.

## Requirements

- PHP 8.3 with PDO PostgreSQL
- Composer 2
- Node.js 22 and npm
- PostgreSQL 17
- Redis 7

## Clean checkout setup

```bash
git clone <repository-url> k-connect
cd k-connect
cp .env.example .env
composer install
php artisan key:generate
npm ci
```

Create the PostgreSQL database and user named in `.env`, then run:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

The local-only seeder creates the `K-Connect Demo` organization and an administrator using `KCONNECT_DEMO_ADMIN_EMAIL` and `KCONNECT_DEMO_ADMIN_PASSWORD`. Change those values before seeding a shared development environment. Production seeding skips this demo account.

## Development checks

```bash
composer validate --strict
vendor/bin/pint --dirty --format agent
composer analyse
php artisan test --compact
npm run build
composer audit --locked
npm audit --audit-level=high
```

PHPUnit is configured to use an in-memory SQLite database so the test suite does not modify the development PostgreSQL database.

## Organization boundary

Every organization-owned Eloquent model uses a request/job-scoped `OrganizationContext`. When a context is active, queries are scoped automatically and new records receive the active `organization_id`; explicitly conflicting ownership is rejected. Queue jobs and request middleware added in later stories must set the context at the lifecycle boundary and clear it in `finally` cleanup.

## Lifecycle write boundary

Lifecycle-managed `status` values must be changed by loading each Eloquent model and saving it so the domain transition rules run. Mass Eloquent status updates, raw-expression status updates, and upserts containing `status` are rejected; mass updates to other columns remain supported. Lower-level `DB::table(...)`, raw SQL, `toBase()`, and direct query-builder writes cannot be intercepted and are unsupported for lifecycle-managed tables. An architecture test prevents those lower-level APIs from being introduced in application code.
