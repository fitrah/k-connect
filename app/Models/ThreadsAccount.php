<?php

namespace App\Models;

use App\Enums\ThreadsAccountStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\EnforcesStatusTransitions;
use Database\Factories\ThreadsAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ThreadsAccount extends Model
{
    /** @use HasFactory<ThreadsAccountFactory> */
    use BelongsToOrganization, EnforcesStatusTransitions, HasFactory;

    protected $fillable = ['organization_id', 'threads_user_id', 'username', 'access_token', 'token_expires_at', 'scopes', 'status', 'is_publishing_enabled'];

    protected $hidden = ['access_token'];

    protected function casts(): array
    {
        return ['access_token' => 'encrypted', 'token_expires_at' => 'datetime', 'scopes' => 'array', 'status' => ThreadsAccountStatus::class, 'is_publishing_enabled' => 'boolean'];
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }
}
