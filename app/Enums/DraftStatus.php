<?php

namespace App\Enums;

enum DraftStatus: string implements LifecycleStatus
{
    case Generated = 'generated';
    case PendingReview = 'pending_review';
    case Edited = 'edited';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Publishing = 'publishing';
    case Published = 'published';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function canTransitionTo(LifecycleStatus $next): bool
    {
        return match ($this) {
            self::Generated => in_array($next, [self::PendingReview, self::Cancelled], true),
            self::PendingReview => in_array($next, [self::Edited, self::Approved, self::Rejected, self::Cancelled], true),
            self::Edited => in_array($next, [self::PendingReview, self::Approved, self::Rejected, self::Cancelled], true),
            self::Approved => in_array($next, [self::Publishing, self::Cancelled], true),
            self::Publishing => in_array($next, [self::Published, self::Failed, self::Cancelled], true),
            self::Failed => in_array($next, [self::Publishing, self::Cancelled], true),
            self::Rejected, self::Published, self::Cancelled => false,
        };
    }
}
