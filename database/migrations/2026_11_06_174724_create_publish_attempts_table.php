<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publish_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reply_draft_id');
            $table->foreignId('threads_account_id');
            $table->unsignedInteger('attempt_number');
            $table->enum('status', ['approved', 'creating_container', 'container_created', 'waiting_container', 'publishing', 'published', 'failed_retryable', 'failed_permanent', 'cancelled'])->default('approved');
            $table->string('container_id')->nullable();
            $table->string('published_media_id')->nullable();
            $table->text('published_permalink')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('is_retryable')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'id']);
            $table->unique(['reply_draft_id', 'attempt_number']);
            $table->unique('published_media_id');
            $table->foreign(['organization_id', 'reply_draft_id'], 'pa_org_draft_fk')->references(['organization_id', 'id'])->on('reply_drafts')->cascadeOnDelete();
            $table->foreign(['organization_id', 'threads_account_id'], 'pa_org_account_fk')->references(['organization_id', 'id'])->on('threads_accounts')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publish_attempts');
    }
};
