<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ReplyRevisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReplyRevision extends Model
{
    /** @use HasFactory<ReplyRevisionFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = ['organization_id', 'reply_draft_id', 'created_by', 'version', 'content', 'reason'];

    public function replyDraft(): BelongsTo
    {
        return $this->belongsTo(ReplyDraft::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
