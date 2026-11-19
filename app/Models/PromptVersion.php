<?php

namespace App\Models;

use App\Enums\PromptStatus;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\EnforcesStatusTransitions;
use Database\Factories\PromptVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromptVersion extends Model
{
    /** @use HasFactory<PromptVersionFactory> */
    use BelongsToOrganization, EnforcesStatusTransitions, HasFactory;

    protected $fillable = ['organization_id', 'created_by', 'type', 'version', 'status', 'system_instruction', 'user_instruction', 'model', 'metadata'];

    protected function casts(): array
    {
        return ['status' => PromptStatus::class, 'metadata' => 'array'];
    }
}
