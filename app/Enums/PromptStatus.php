<?php

namespace App\Enums;

enum PromptStatus: string implements LifecycleStatus
{
    case Draft = 'draft';
    case Active = 'active';
    case Retired = 'retired';

    public function canTransitionTo(LifecycleStatus $next): bool
    {
        return match ($this) {
            self::Draft => in_array($next, [self::Active, self::Retired], true),
            self::Active => $next === self::Retired,
            self::Retired => false,
        };
    }
}
