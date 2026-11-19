<?php

namespace Database\Factories;

use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @extends Factory<TModel>
 */
abstract class OrganizationOwnedFactory extends Factory
{
    public function create($attributes = [], ?Model $parent = null)
    {
        $context = app(OrganizationContext::class);

        if ($context->id() !== null || $context->isSystemBypassed()) {
            return parent::create($attributes, $parent);
        }

        return $context->runAsSystem(
            fn () => parent::create($attributes, $parent),
        );
    }
}
