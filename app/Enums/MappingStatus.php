<?php

namespace App\Enums;

enum MappingStatus: string implements LifecycleStatus
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    public function canTransitionTo(LifecycleStatus $next): bool
    {
        return match ($this) {
            self::Draft => in_array($next, [self::Active, self::Archived], true),
            self::Active => in_array($next, [self::Inactive, self::Archived], true),
            self::Inactive => in_array($next, [self::Active, self::Archived], true),
            self::Archived => false,
        };
    }
}
