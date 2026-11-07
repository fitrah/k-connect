<?php

namespace App\Enums;

enum ScheduledJobStatus: string implements LifecycleStatus
{
    case Pending = 'pending';
    case Running = 'running';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function canTransitionTo(LifecycleStatus $next): bool
    {
        return match ($this) {
            self::Pending => in_array($next, [self::Running, self::Skipped], true),
            self::Running => in_array($next, [self::Succeeded, self::Failed, self::Skipped], true),
            self::Failed => $next === self::Running,
            self::Succeeded, self::Skipped => false,
        };
    }
}
