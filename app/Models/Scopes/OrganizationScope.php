<?php

namespace App\Models\Scopes;

use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use LogicException;

class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(OrganizationContext::class);
        $organizationId = $context->id();

        if ($context->isSystemBypassed()) {
            return;
        }

        if ($organizationId === null) {
            throw new LogicException('Organization context is required for organization-owned queries.');
        }

        $builder->where($model->qualifyColumn('organization_id'), $organizationId);
    }
}
