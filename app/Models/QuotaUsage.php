<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\QuotaUsageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotaUsage extends Model
{
    /** @use HasFactory<QuotaUsageFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = ['organization_id', 'threads_account_id', 'campaign_id', 'keyword_id', 'operation', 'window_started_at', 'window_ends_at', 'used', 'limit', 'metadata'];

    protected function casts(): array
    {
        return ['window_started_at' => 'datetime', 'window_ends_at' => 'datetime', 'metadata' => 'array'];
    }

    public function threadsAccount(): BelongsTo
    {
        return $this->belongsTo(ThreadsAccount::class);
    }
}
