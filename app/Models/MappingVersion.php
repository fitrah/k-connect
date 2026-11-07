<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MappingVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MappingVersion extends Model
{
    /** @use HasFactory<MappingVersionFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = ['organization_id', 'response_mapping_id', 'created_by', 'version', 'configuration', 'change_summary'];

    protected function casts(): array
    {
        return ['configuration' => 'array'];
    }

    public function responseMapping(): BelongsTo
    {
        return $this->belongsTo(ResponseMapping::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
