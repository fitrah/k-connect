<?php

namespace App\Models;

use App\Enums\MappingStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\EnforcesStatusTransitions;
use Database\Factories\ResponseMappingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResponseMapping extends Model
{
    /** @use HasFactory<ResponseMappingFactory> */
    use BelongsToOrganization, EnforcesStatusTransitions, HasFactory;

    protected $fillable = ['organization_id', 'campaign_id', 'fallback_response_mapping_id', 'name', 'priority', 'status', 'applicable_intents', 'human_approval_required', 'auto_reply_allowed', 'confidence_threshold'];

    protected function casts(): array
    {
        return ['status' => MappingStatus::class, 'applicable_intents' => 'array', 'human_approval_required' => 'boolean', 'auto_reply_allowed' => 'boolean', 'confidence_threshold' => 'decimal:4'];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function fallbackResponseMapping(): BelongsTo
    {
        return $this->belongsTo(self::class, 'fallback_response_mapping_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(MappingVersion::class);
    }
}
