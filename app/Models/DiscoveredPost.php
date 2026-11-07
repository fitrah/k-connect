<?php

namespace App\Models;

use App\Enums\DiscoveredPostStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\EnforcesStatusTransitions;
use Database\Factories\DiscoveredPostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscoveredPost extends Model
{
    /** @use HasFactory<DiscoveredPostFactory> */
    use BelongsToOrganization, EnforcesStatusTransitions, HasFactory;

    protected $fillable = ['organization_id', 'threads_account_id', 'threads_media_id', 'author_threads_id', 'author_username', 'caption', 'media_type', 'media_url', 'permalink', 'posted_at', 'status', 'rejection_reason_code', 'metadata'];

    protected function casts(): array
    {
        return ['posted_at' => 'datetime', 'status' => DiscoveredPostStatus::class, 'metadata' => 'array'];
    }

    public function threadsAccount(): BelongsTo
    {
        return $this->belongsTo(ThreadsAccount::class);
    }

    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(Keyword::class)
            ->withPivotValue('organization_id', $this->organization_id)
            ->withPivot(['keyword_search_run_id', 'first_discovered_at']);
    }

    public function classifications(): HasMany
    {
        return $this->hasMany(PostClassification::class);
    }

    public function replyDrafts(): HasMany
    {
        return $this->hasMany(ReplyDraft::class);
    }
}
