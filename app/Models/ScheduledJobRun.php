<?php

namespace App\Models;

use App\Enums\ScheduledJobStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\EnforcesStatusTransitions;
use Database\Factories\ScheduledJobRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledJobRun extends Model
{
    /** @use HasFactory<ScheduledJobRunFactory> */
    use BelongsToOrganization, EnforcesStatusTransitions, HasFactory;

    protected $fillable = ['organization_id', 'job_name', 'status', 'started_at', 'finished_at', 'duration_ms', 'processed_count', 'succeeded_count', 'failed_count', 'error_message', 'correlation_id', 'metadata'];

    protected function casts(): array
    {
        return ['status' => ScheduledJobStatus::class, 'started_at' => 'datetime', 'finished_at' => 'datetime', 'metadata' => 'array'];
    }
}
