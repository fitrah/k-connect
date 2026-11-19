<?php

namespace App\Models\Builders;

use LogicException;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends OrganizationEloquentBuilder<TModel>
 */
class LifecycleEloquentBuilder extends OrganizationEloquentBuilder
{
    public function update(array $values): int
    {
        $this->rejectStatusValues($values);

        return parent::update($values);
    }

    public function upsert(array $values, $uniqueBy, $update = null): int
    {
        $rows = isset($values[0]) && is_array($values[0]) ? $values : [$values];

        foreach ($rows as $row) {
            $this->rejectStatusValues($row);
        }

        if (is_array($update) && in_array('status', $update, true)) {
            $this->rejectStatusWrite();
        }

        return parent::upsert($values, $uniqueBy, $update);
    }

    public function updateFrom(array $values): int
    {
        $this->rejectStatusValues($values);

        return parent::updateFrom($values);
    }

    public function updateOrInsert(array $attributes, array|callable $values = []): bool
    {
        $this->rejectStatusValues($attributes);

        if (is_callable($values)) {
            $values = function (bool $exists) use ($values): array {
                $resolvedValues = $values($exists);
                $this->rejectStatusValues($resolvedValues);

                return $resolvedValues;
            };
        } else {
            $this->rejectStatusValues($values);
        }

        return parent::updateOrInsert($attributes, $values);
    }

    public function increment($column, $amount = 1, array $extra = []): int
    {
        $this->rejectStatusColumn($column);
        $this->rejectStatusValues($extra);

        return parent::increment($column, $amount, $extra);
    }

    public function decrement($column, $amount = 1, array $extra = []): int
    {
        $this->rejectStatusColumn($column);
        $this->rejectStatusValues($extra);

        return parent::decrement($column, $amount, $extra);
    }

    public function incrementEach(array $columns, array $extra = []): int
    {
        $this->rejectStatusValues($columns);
        $this->rejectStatusValues($extra);

        return parent::incrementEach($columns, $extra);
    }

    public function decrementEach(array $columns, array $extra = []): int
    {
        $this->rejectStatusValues($columns);
        $this->rejectStatusValues($extra);

        return parent::decrementEach($columns, $extra);
    }

    /**
     * @param  array<string|int, mixed>  $values
     */
    private function rejectStatusValues(array $values): void
    {
        if (array_key_exists('status', $values)) {
            $this->rejectStatusWrite();
        }
    }

    private function rejectStatusColumn(mixed $column): void
    {
        if ($column === 'status') {
            $this->rejectStatusWrite();
        }
    }

    private function rejectStatusWrite(): never
    {
        throw new LogicException(sprintf(
            'Mass lifecycle status writes are not supported for %s; load each model and persist the transition through model events.',
            $this->getModel()::class,
        ));
    }
}
