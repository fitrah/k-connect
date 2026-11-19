<?php

namespace App\Support;

use App\Models\Organization;
use Closure;

class OrganizationContext
{
    private ?Organization $organization = null;

    private int $systemBypassDepth = 0;

    public function set(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function clear(): void
    {
        $this->organization = null;
    }

    public function organization(): ?Organization
    {
        return $this->organization;
    }

    public function id(): ?int
    {
        return $this->organization?->getKey();
    }

    public function isSystemBypassed(): bool
    {
        return $this->systemBypassDepth > 0;
    }

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function runAsSystem(Closure $callback): mixed
    {
        $this->systemBypassDepth++;

        try {
            return $callback();
        } finally {
            $this->systemBypassDepth--;
        }
    }
}
