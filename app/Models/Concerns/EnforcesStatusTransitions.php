<?php

namespace App\Models\Concerns;

use App\Enums\LifecycleStatus;
use App\Models\Builders\LifecycleEloquentBuilder;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

trait EnforcesStatusTransitions
{
    /**
     * @param  Builder<static>  $query
     */
    protected function performUpdate(Builder $query): bool
    {
        if (! $query instanceof LifecycleEloquentBuilder) {
            throw new LogicException(sprintf('%s must use the lifecycle Eloquent builder.', static::class));
        }

        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        $dirty = $this->getDirtyForUpdate();

        if (count($dirty) > 0) {
            if (app(OrganizationContext::class)->isSystemBypassed()) {
                throw new LogicException('System organization writes must constrain organization_id explicitly.');
            }

            $scopedQuery = $this->setKeysForSaveQuery($query)->applyScopes();
            $scopedQuery->getQuery()->update($dirty);

            $this->refreshSavedAttributes();
            $this->syncChanges();
            $this->fireModelEvent('updated', false);
        }

        return true;
    }

    public static function bootEnforcesStatusTransitions(): void
    {
        static::updating(function (self $model): void {
            if (! $model->isDirty('status')) {
                return;
            }

            $enumClass = $model->getCasts()['status'] ?? null;
            $originalValue = $model->getRawOriginal('status');
            $next = $model->getAttribute('status');

            if (! is_string($enumClass) || ! enum_exists($enumClass) || ! is_subclass_of($enumClass, LifecycleStatus::class)) {
                throw new LogicException(sprintf('%s must cast status to a lifecycle enum.', $model::class));
            }

            $current = $enumClass::from($originalValue);

            if (! $next instanceof LifecycleStatus) {
                throw new LogicException(sprintf('%s must cast status to a lifecycle enum.', $model::class));
            }

            if (! $current->canTransitionTo($next)) {
                throw new LogicException(sprintf(
                    'Illegal status transition for %s from %s to %s.',
                    $model::class,
                    $current->value,
                    $next->value,
                ));
            }
        });
    }
}
