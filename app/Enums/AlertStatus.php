<?php

namespace App\Enums;

enum AlertStatus: string implements LifecycleStatus
{
    case Open = 'open';
    case Acknowledged = 'acknowledged';
    case Resolved = 'resolved';

    public function canTransitionTo(LifecycleStatus $next): bool
    {
        return match ($this) {
            self::Open => in_array($next, [self::Acknowledged, self::Resolved], true),
            self::Acknowledged => $next === self::Resolved,
            self::Resolved => false,
        };
    }
}
