<?php

namespace App\Models\Builders;

use App\Models\Organization;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use LogicException;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends Builder<TModel>
 */
class OrganizationEloquentBuilder extends Builder
{
    public function __construct(
        QueryBuilder $query,
        private ?int $organizationBoundaryId,
        private readonly bool $systemBypassed,
    ) {
        parent::__construct($query);
    }

    public function applyScopes()
    {
        $this->assertOrganizationContextMatchesBuilder();
        $builder = parent::applyScopes();

        if ($builder->systemBypassed && $builder->organizationBoundaryId === null) {
            return $builder;
        }

        if ($builder->organizationBoundaryId === null) {
            throw new LogicException('Organization context is required for organization-owned queries.');
        }

        $builder->callScope(function (self $scopedBuilder): void {
            $scopedBuilder->where(
                $scopedBuilder->getModel()->qualifyColumn('organization_id'),
                $scopedBuilder->organizationBoundaryId,
            );
        });

        return $builder;
    }

    public function forSystemOrganization(Organization|int $organization): static
    {
        $this->assertOrganizationContextMatchesBuilder();

        if (! $this->systemBypassed) {
            throw new LogicException('System organization boundaries require the system bypass callback.');
        }

        $builder = clone $this;
        $builder->organizationBoundaryId = (int) ($organization instanceof Organization ? $organization->getKey() : $organization);

        return $builder;
    }

    public function insert(array $values): bool
    {
        if ($values === []) {
            return true;
        }

        return $this->getQuery()->insert($this->validatedInsertRows($values));
    }

    public function insertOrIgnore(array $values): int
    {
        if ($values === []) {
            return 0;
        }

        return $this->getQuery()->insertOrIgnore($this->validatedInsertRows($values));
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function insertGetId(array $values, $sequence = null): int
    {
        $rows = $this->validatedInsertRows($values);

        return $this->getQuery()->insertGetId($rows[0], $sequence);
    }

    /**
     * @param  array<int, string>  $returning
     * @param  non-empty-string|non-empty-array<non-empty-string>|null  $uniqueBy
     * @return Collection<int, object>
     */
    public function insertOrIgnoreReturning(array $values, array $returning = ['*'], array|string|null $uniqueBy = null): Collection
    {
        if ($values === []) {
            return new Collection;
        }

        return $this->getQuery()->insertOrIgnoreReturning(
            $this->validatedInsertRows($values),
            $returning,
            $uniqueBy,
        );
    }

    public function insertUsing(array $columns, $query): never
    {
        $this->rejectUnverifiableInsertUsing();
    }

    public function insertOrIgnoreUsing(array $columns, $query): never
    {
        $this->rejectUnverifiableInsertUsing();
    }

    public function update(array $values): int
    {
        $this->requireOrganizationWriteScope();
        $this->rejectOrganizationReassignment($values);

        return parent::update($values);
    }

    public function updateFrom(array $values): int
    {
        $this->requireOrganizationWriteScope();
        $this->rejectOrganizationReassignment($values);

        return $this->toBase()->updateFrom($values);
    }

    public function upsert(array $values, $uniqueBy, $update = null): int
    {
        if ($values === []) {
            return 0;
        }

        $rows = $this->validatedInsertRows($values);
        $uniqueColumns = is_array($uniqueBy) ? $uniqueBy : [$uniqueBy];

        if (! in_array('organization_id', $uniqueColumns, true)) {
            throw new LogicException('Organization-owned upserts must include organization_id in its unique key.');
        }

        if ($update === null) {
            $update = array_values(array_diff(array_keys($rows[0]), ['organization_id']));
        } else {
            $this->rejectOrganizationUpdateColumns($update);
        }

        return parent::upsert($rows, $uniqueBy, $update);
    }

    public function updateOrInsert(array $attributes, array|callable $values = []): bool
    {
        $attributes = $this->validatedOwnershipAttributes($attributes);
        $exists = $this->where($attributes)->exists();

        if (is_callable($values)) {
            $values = $values($exists);
        }

        $this->rejectOrganizationReassignment($values);

        if (! $exists) {
            return $this->insert(array_merge($attributes, $values));
        }

        if ($values === []) {
            return true;
        }

        return (bool) $this->limit(1)->update($values);
    }

    public function increment($column, $amount = 1, array $extra = []): int
    {
        $this->requireOrganizationWriteScope();
        $this->rejectOrganizationColumn($column);
        $this->rejectOrganizationReassignment($extra);

        return parent::increment($column, $amount, $extra);
    }

    public function decrement($column, $amount = 1, array $extra = []): int
    {
        $this->requireOrganizationWriteScope();
        $this->rejectOrganizationColumn($column);
        $this->rejectOrganizationReassignment($extra);

        return parent::decrement($column, $amount, $extra);
    }

    public function incrementEach(array $columns, array $extra = []): int
    {
        $this->requireOrganizationWriteScope();
        $this->rejectOrganizationReassignment($columns);
        $this->rejectOrganizationReassignment($extra);

        return parent::incrementEach($columns, $extra);
    }

    public function decrementEach(array $columns, array $extra = []): int
    {
        $this->requireOrganizationWriteScope();
        $this->rejectOrganizationReassignment($columns);
        $this->rejectOrganizationReassignment($extra);

        return parent::decrementEach($columns, $extra);
    }

    public function delete(): mixed
    {
        $this->requireOrganizationWriteScope();

        return parent::delete();
    }

    public function forceDelete(): int
    {
        $this->requireOrganizationWriteScope();

        return $this->toBase()->delete();
    }

    public function truncate(): never
    {
        throw new LogicException(sprintf(
            'Bulk truncate is not supported for organization-owned model %s.',
            $this->getModel()::class,
        ));
    }

    /**
     * @param  array<int|string, mixed>  $values
     * @return list<array<string, mixed>>
     */
    private function validatedInsertRows(array $values): array
    {
        $this->assertOrganizationContextMatchesBuilder();
        $context = app(OrganizationContext::class);
        $organizationId = $context->id();
        $rows = isset($values[0]) && is_array($values[0]) ? $values : [$values];

        if (! $context->isSystemBypassed() && $organizationId === null) {
            throw new LogicException('Organization context is required for organization-owned writes.');
        }

        foreach ($rows as &$row) {
            if (! array_key_exists('organization_id', $row) || $row['organization_id'] === null) {
                if ($context->isSystemBypassed()) {
                    throw new LogicException('System organization writes must supply organization_id explicitly.');
                }

                $row['organization_id'] = $organizationId;

                continue;
            }

            if (! $context->isSystemBypassed() && (int) $row['organization_id'] !== $organizationId) {
                throw new LogicException('The supplied organization_id conflicts with the active organization.');
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function validatedOwnershipAttributes(array $attributes): array
    {
        $this->assertOrganizationContextMatchesBuilder();
        $context = app(OrganizationContext::class);
        $organizationId = $context->id();

        if ($context->isSystemBypassed()) {
            if (! array_key_exists('organization_id', $attributes) || $attributes['organization_id'] === null) {
                throw new LogicException('System organization writes must supply organization_id explicitly.');
            }

            return $attributes;
        }

        if ($organizationId === null) {
            throw new LogicException('Organization context is required for organization-owned writes.');
        }

        if (array_key_exists('organization_id', $attributes) && (int) $attributes['organization_id'] !== $organizationId) {
            throw new LogicException('The supplied organization_id conflicts with the active organization.');
        }

        $attributes['organization_id'] = $organizationId;

        return $attributes;
    }

    private function requireOrganizationWriteScope(): void
    {
        $this->assertOrganizationContextMatchesBuilder();
        $context = app(OrganizationContext::class);

        if ($context->isSystemBypassed()) {
            if ($this->organizationBoundaryId === null) {
                throw new LogicException('System organization writes must constrain organization_id explicitly.');
            }

            return;
        }

        if ($context->id() === null) {
            throw new LogicException('Organization context is required for organization-owned writes.');
        }
    }

    private function assertOrganizationContextMatchesBuilder(): void
    {
        $context = app(OrganizationContext::class);

        if (
            $context->isSystemBypassed() !== $this->systemBypassed
            || (! $this->systemBypassed && $context->id() !== $this->organizationBoundaryId)
        ) {
            throw new LogicException('Organization context changed after the organization-owned query was created.');
        }
    }

    /**
     * @param  array<string|int, mixed>  $values
     */
    private function rejectOrganizationReassignment(array $values): void
    {
        if (array_key_exists('organization_id', $values)) {
            $this->rejectOrganizationWrite();
        }
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function rejectOrganizationUpdateColumns(array $columns): void
    {
        if (in_array('organization_id', $columns, true)) {
            $this->rejectOrganizationWrite();
        }
    }

    private function rejectOrganizationColumn(mixed $column): void
    {
        if ($column === 'organization_id') {
            $this->rejectOrganizationWrite();
        }
    }

    private function rejectOrganizationWrite(): never
    {
        throw new LogicException(sprintf(
            'Bulk organization_id reassignment is not supported for %s.',
            $this->getModel()::class,
        ));
    }

    private function rejectUnverifiableInsertUsing(): never
    {
        throw new LogicException(sprintf(
            'Bulk insertUsing writes are not supported for %s because the builder cannot validate organization ownership.',
            $this->getModel()::class,
        ));
    }
}
