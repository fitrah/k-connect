<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\EnforcesStatusTransitions;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['organization_id', 'threads_account_id', 'default_response_mapping_id', 'name', 'description', 'status', 'default_language', 'timezone', 'active_hours', 'approval_mode', 'confidence_threshold', 'hourly_reply_limit', 'daily_reply_limit', 'starts_at', 'ends_at'])]
class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use BelongsToOrganization, EnforcesStatusTransitions, HasFactory;

    protected function casts(): array
    {
        return ['status' => CampaignStatus::class, 'active_hours' => 'array', 'confidence_threshold' => 'decimal:4', 'starts_at' => 'date', 'ends_at' => 'date'];
    }

    public function threadsAccount(): BelongsTo
    {
        return $this->belongsTo(ThreadsAccount::class);
    }

    public function defaultResponseMapping(): BelongsTo
    {
        return $this->belongsTo(ResponseMapping::class, 'default_response_mapping_id');
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(Keyword::class);
    }
}
