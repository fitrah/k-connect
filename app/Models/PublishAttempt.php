<?php

namespace App\Models;

use App\Enums\PublishAttemptStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\EnforcesStatusTransitions;
use Database\Factories\PublishAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublishAttempt extends Model
{
    /** @use HasFactory<PublishAttemptFactory> */
    use BelongsToOrganization, EnforcesStatusTransitions, HasFactory;

    protected $fillable = ['organization_id', 'reply_draft_id', 'threads_account_id', 'attempt_number', 'status', 'container_id', 'published_media_id', 'published_permalink', 'error_code', 'error_message', 'is_retryable', 'started_at', 'finished_at'];

    protected function casts(): array
    {
        return ['status' => PublishAttemptStatus::class, 'is_retryable' => 'boolean', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function replyDraft(): BelongsTo
    {
        return $this->belongsTo(ReplyDraft::class);
    }

    public function threadsAccount(): BelongsTo
    {
        return $this->belongsTo(ThreadsAccount::class);
    }
}
