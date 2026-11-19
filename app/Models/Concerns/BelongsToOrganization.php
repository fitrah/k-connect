<?php

namespace App\Models\Concerns;

use App\Models\Builders\LifecycleEloquentBuilder;
use App\Models\Builders\OrganizationEloquentBuilder;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use LogicException;

trait BelongsToOrganization
{
    /**
     * @param  QueryBuilder  $query
     * @return OrganizationEloquentBuilder<$this>
     */
    public function newEloquentBuilder($query): OrganizationEloquentBuilder
    {
        $context = app(OrganizationContext::class);
        $builder = in_array(EnforcesStatusTransitions::class, class_uses_recursive($this), true)
            ? new LifecycleEloquentBuilder($query, $context->id(), $context->isSystemBypassed())
            : new OrganizationEloquentBuilder($query, $context->id(), $context->isSystemBypassed());

        return $builder->setModel($this);
    }

    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(function (self $model): void {
            $context = app(OrganizationContext::class);
            $organizationId = $context->id();
            $modelOrganizationId = $model->getAttribute('organization_id');

            if ($context->isSystemBypassed()) {
                if ($modelOrganizationId === null) {
                    throw new LogicException('System organization writes must supply organization_id explicitly.');
                }

                return;
            }

            if ($organizationId === null) {
                throw new LogicException('Organization context is required for organization-owned writes.');
            }

            if ($modelOrganizationId !== null && (int) $modelOrganizationId !== $organizationId) {
                throw new LogicException('The supplied organization_id conflicts with the active organization.');
            }

            $model->organization_id = $organizationId;
        });

        static::updating(function (self $model): void {
            $context = app(OrganizationContext::class);
            $organizationId = $context->id();

            if ($context->isSystemBypassed()) {
                return;
            }

            if ($organizationId === null) {
                throw new LogicException('Organization context is required for organization-owned writes.');
            }

            if ((int) $model->organization_id !== $organizationId) {
                throw new LogicException('The supplied organization_id conflicts with the active organization.');
            }
        });

        static::deleting(function (self $model): void {
            $context = app(OrganizationContext::class);
            $organizationId = $context->id();

            if ($context->isSystemBypassed()) {
                return;
            }

            if ($organizationId === null) {
                throw new LogicException('Organization context is required for organization-owned writes.');
            }

            if ((int) $model->organization_id !== $organizationId) {
                throw new LogicException('The supplied organization_id conflicts with the active organization.');
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
