<?php

namespace Tests\Unit;

use App\Enums\CampaignStatus;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_archived_campaign_status_is_terminal(): void
    {
        foreach (CampaignStatus::cases() as $nextStatus) {
            $this->assertFalse(CampaignStatus::Archived->canTransitionTo($nextStatus));
        }
    }
}
