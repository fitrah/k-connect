<?php

namespace App\Models;

use App\Enums\ScheduledJobStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\EnforcesStatusTransitions;
use Database\Factories\KeywordSearchRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeywordSearchRun extends Model
{
    /** @use HasFactory<KeywordSearchRunFactory> */
    use BelongsToOrganization, EnforcesStatusTransitions, HasFactory;

    protected $fillable = ['organization_id', 'keyword_id', 'status', 'request_metadata', 'result_count', 'new_post_count', 'quota_estimate', 'duration_ms', 'error_code', 'error_message', 'started_at', 'finished_at'];

    protected function casts(): array
    {
        return ['status' => ScheduledJobStatus::class, 'request_metadata' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }
}
