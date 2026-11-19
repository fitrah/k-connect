<?php

namespace App\Enums;

enum DiscoveredPostStatus: string implements LifecycleStatus
{
    case New = 'new';
    case RuleRejected = 'rule_rejected';
    case AwaitingClassification = 'awaiting_classification';
    case ClassifiedIrrelevant = 'classified_irrelevant';
    case ClassifiedRelevant = 'classified_relevant';
    case AwaitingDraft = 'awaiting_draft';
    case ReadyForReview = 'ready_for_review';
    case AutoReplyEligible = 'auto_reply_eligible';
    case Ignored = 'ignored';
    case Blocked = 'blocked';
    case ProcessingFailed = 'processing_failed';

    public function canTransitionTo(LifecycleStatus $next): bool
    {
        return match ($this) {
            self::New => in_array($next, [self::RuleRejected, self::AwaitingClassification, self::Ignored, self::Blocked], true),
            self::AwaitingClassification => in_array($next, [self::ClassifiedIrrelevant, self::ClassifiedRelevant, self::ProcessingFailed, self::Blocked], true),
            self::ClassifiedRelevant => in_array($next, [self::AwaitingDraft, self::Ignored, self::Blocked], true),
            self::AwaitingDraft => in_array($next, [self::ReadyForReview, self::AutoReplyEligible, self::ProcessingFailed, self::Blocked], true),
            self::ProcessingFailed => in_array($next, [self::AwaitingClassification, self::AwaitingDraft, self::Ignored, self::Blocked], true),
            self::RuleRejected, self::ClassifiedIrrelevant, self::ReadyForReview, self::AutoReplyEligible, self::Ignored, self::Blocked => false,
        };
    }
}
