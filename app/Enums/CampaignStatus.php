<?php

namespace App\Enums;

enum CampaignStatus: string implements LifecycleStatus
{
    case Draft = 'draft';
    case Active = 'active';
    case Paused = 'paused';
    case Archived = 'archived';

    public function canTransitionTo(LifecycleStatus $next): bool
    {
        return match ($this) {
            self::Draft => in_array($next, [self::Active, self::Archived], true),
            self::Active => in_array($next, [self::Paused, self::Archived], true),
            self::Paused => in_array($next, [self::Active, self::Archived], true),
            self::Archived => false,
        };
    }
}
