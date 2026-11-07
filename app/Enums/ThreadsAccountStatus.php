<?php

namespace App\Enums;

enum ThreadsAccountStatus: string implements LifecycleStatus
{
    case Connected = 'connected';
    case Expiring = 'expiring';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case Disconnected = 'disconnected';

    public function canTransitionTo(LifecycleStatus $next): bool
    {
        return match ($this) {
            self::Connected => in_array($next, [self::Expiring, self::Expired, self::Revoked, self::Disconnected], true),
            self::Expiring => in_array($next, [self::Connected, self::Expired, self::Revoked, self::Disconnected], true),
            self::Expired => in_array($next, [self::Connected, self::Revoked, self::Disconnected], true),
            self::Disconnected => $next === self::Connected,
            self::Revoked => false,
        };
    }
}
