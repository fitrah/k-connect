<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\PostClassificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostClassification extends Model
{
    /** @use HasFactory<PostClassificationFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = ['organization_id', 'discovered_post_id', 'prompt_version_id', 'is_relevant', 'confidence', 'intent', 'language', 'sentiment', 'risk_flags', 'reason', 'recommended_action', 'model', 'latency_ms', 'input_tokens', 'output_tokens', 'raw_output'];

    protected function casts(): array
    {
        return ['is_relevant' => 'boolean', 'confidence' => 'decimal:4', 'risk_flags' => 'array', 'raw_output' => 'array'];
    }

    public function discoveredPost(): BelongsTo
    {
        return $this->belongsTo(DiscoveredPost::class);
    }

    public function promptVersion(): BelongsTo
    {
        return $this->belongsTo(PromptVersion::class);
    }
}
