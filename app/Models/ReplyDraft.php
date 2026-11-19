<?php

namespace App\Models;

use App\Enums\DraftStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\EnforcesStatusTransitions;
use Database\Factories\ReplyDraftFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReplyDraft extends Model
{
    /** @use HasFactory<ReplyDraftFactory> */
    use BelongsToOrganization, EnforcesStatusTransitions, HasFactory;

    protected $fillable = ['organization_id', 'discovered_post_id', 'post_classification_id', 'mapping_version_id', 'prompt_version_id', 'reviewed_by', 'status', 'content', 'language', 'warnings', 'requires_human_review', 'approved_at', 'rejected_at', 'rejection_reason'];

    protected function casts(): array
    {
        return ['status' => DraftStatus::class, 'warnings' => 'array', 'requires_human_review' => 'boolean', 'approved_at' => 'datetime', 'rejected_at' => 'datetime'];
    }

    public function discoveredPost(): BelongsTo
    {
        return $this->belongsTo(DiscoveredPost::class);
    }

    public function postClassification(): BelongsTo
    {
        return $this->belongsTo(PostClassification::class);
    }

    public function mappingVersion(): BelongsTo
    {
        return $this->belongsTo(MappingVersion::class);
    }

    public function promptVersion(): BelongsTo
    {
        return $this->belongsTo(PromptVersion::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ReplyRevision::class);
    }

    public function publishAttempts(): HasMany
    {
        return $this->hasMany(PublishAttempt::class);
    }
}
