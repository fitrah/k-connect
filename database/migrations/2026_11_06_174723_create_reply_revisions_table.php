<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reply_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reply_draft_id');
            $table->foreignId('created_by')->nullable();
            $table->unsignedInteger('version');
            $table->text('content');
            $table->string('reason')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'id']);
            $table->unique(['reply_draft_id', 'version']);
            $table->foreign(['organization_id', 'reply_draft_id'], 'rr_org_draft_fk')->references(['organization_id', 'id'])->on('reply_drafts')->cascadeOnDelete();
            $table->foreign(['organization_id', 'created_by'], 'rr_org_creator_fk')->references(['organization_id', 'id'])->on('users')->deferrable()->initiallyImmediate(false);
            $table->foreign('created_by', 'rr_creator_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reply_revisions');
    }
};
