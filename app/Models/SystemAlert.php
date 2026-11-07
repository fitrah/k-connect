<?php

namespace App\Models;

use App\Enums\AlertStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\EnforcesStatusTransitions;
use Database\Factories\SystemAlertFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemAlert extends Model
{
    /** @use HasFactory<SystemAlertFactory> */
    use BelongsToOrganization, EnforcesStatusTransitions, HasFactory;

    protected $fillable = ['organization_id', 'acknowledged_by', 'type', 'severity', 'status', 'title', 'message', 'source_type', 'source_id', 'context', 'acknowledged_at', 'resolved_at'];

    protected function casts(): array
    {
        return ['status' => AlertStatus::class, 'context' => 'array', 'acknowledged_at' => 'datetime', 'resolved_at' => 'datetime'];
    }
}
