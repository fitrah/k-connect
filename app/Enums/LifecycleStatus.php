<?php

namespace App\Enums;

use BackedEnum;

interface LifecycleStatus extends BackedEnum
{
    public function canTransitionTo(self $next): bool;
}
