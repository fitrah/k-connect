<?php

namespace App\Models;

use App\Enums\KeywordStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\EnforcesStatusTransitions;
use Database\Factories\KeywordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Keyword extends Model
{
    /** @use HasFactory<KeywordFactory> */
    use BelongsToOrganization, EnforcesStatusTransitions, HasFactory;

    protected $fillable = ['organization_id', 'campaign_id', 'response_mapping_id', 'query', 'search_mode', 'search_type', 'media_type', 'author_username', 'include_terms', 'exclude_terms', 'language', 'interval_minutes', 'priority', 'active_hours', 'last_searched_at', 'next_search_at', 'status'];

    protected function casts(): array
    {
        return ['include_terms' => 'array', 'exclude_terms' => 'array', 'active_hours' => 'array', 'last_searched_at' => 'datetime', 'next_search_at' => 'datetime', 'status' => KeywordStatus::class];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function responseMapping(): BelongsTo
    {
        return $this->belongsTo(ResponseMapping::class);
    }

    public function searchRuns(): HasMany
    {
        return $this->hasMany(KeywordSearchRun::class);
    }

    public function discoveredPosts(): BelongsToMany
    {
        return $this->belongsToMany(DiscoveredPost::class)
            ->withPivotValue('organization_id', $this->organization_id)
            ->withPivot(['keyword_search_run_id', 'first_discovered_at']);
    }
}
