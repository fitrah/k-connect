<?php

namespace App\Enums;

enum PublishAttemptStatus: string implements LifecycleStatus
{
    case Approved = 'approved';
    case CreatingContainer = 'creating_container';
    case ContainerCreated = 'container_created';
    case WaitingContainer = 'waiting_container';
    case Publishing = 'publishing';
    case Published = 'published';
    case FailedRetryable = 'failed_retryable';
    case FailedPermanent = 'failed_permanent';
    case Cancelled = 'cancelled';

    public function canTransitionTo(LifecycleStatus $next): bool
    {
        return match ($this) {
            self::Approved => in_array($next, [self::CreatingContainer, self::Cancelled], true),
            self::CreatingContainer => in_array($next, [self::ContainerCreated, self::FailedRetryable, self::FailedPermanent, self::Cancelled], true),
            self::ContainerCreated => in_array($next, [self::WaitingContainer, self::Publishing, self::FailedRetryable, self::FailedPermanent, self::Cancelled], true),
            self::WaitingContainer => in_array($next, [self::Publishing, self::FailedRetryable, self::FailedPermanent, self::Cancelled], true),
            self::Publishing => in_array($next, [self::Published, self::FailedRetryable, self::FailedPermanent, self::Cancelled], true),
            self::FailedRetryable => in_array($next, [self::CreatingContainer, self::WaitingContainer, self::Publishing, self::Cancelled], true),
            self::Published, self::FailedPermanent, self::Cancelled => false,
        };
    }
}
