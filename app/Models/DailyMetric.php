<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\DailyMetricFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyMetric extends Model
{
    /** @use HasFactory<DailyMetricFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = ['organization_id', 'metric_date', 'campaign_id', 'keyword_id', 'response_mapping_id', 'threads_account_id', 'searches', 'discovered_posts', 'relevant_posts', 'drafts_generated', 'approved_drafts', 'rejected_drafts', 'published_replies', 'failed_replies', 'llm_input_tokens', 'llm_output_tokens', 'estimated_cost'];

    protected function casts(): array
    {
        return ['metric_date' => 'date', 'estimated_cost' => 'decimal:6'];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
