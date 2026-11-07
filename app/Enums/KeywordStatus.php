<?php

namespace App\Enums;

enum KeywordStatus: string implements LifecycleStatus
{
    case Draft = 'draft';
    case Active = 'active';
    case Paused = 'paused';
    case QuotaPaused = 'quota_paused';
    case Error = 'error';
    case Archived = 'archived';

    public function canTransitionTo(LifecycleStatus $next): bool
    {
        return match ($this) {
            self::Draft => in_array($next, [self::Active, self::Archived], true),
            self::Active => in_array($next, [self::Paused, self::QuotaPaused, self::Error, self::Archived], true),
            self::Paused, self::QuotaPaused, self::Error => in_array($next, [self::Active, self::Archived], true),
            self::Archived => false,
        };
    }
}
