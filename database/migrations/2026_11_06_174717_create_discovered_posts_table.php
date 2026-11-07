<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discovered_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('threads_account_id');
            $table->string('threads_media_id')->unique();
            $table->string('author_threads_id')->nullable();
            $table->string('author_username')->nullable();
            $table->text('caption')->nullable();
            $table->string('media_type')->nullable();
            $table->text('media_url')->nullable();
            $table->text('permalink')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->enum('status', ['new', 'rule_rejected', 'awaiting_classification', 'classified_irrelevant', 'classified_relevant', 'awaiting_draft', 'ready_for_review', 'auto_reply_eligible', 'ignored', 'blocked', 'processing_failed'])->default('new');
            $table->string('rejection_reason_code')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'status', 'posted_at']);
            $table->index(['organization_id', 'author_username']);
            $table->foreign(['organization_id', 'threads_account_id'], 'dp_org_account_fk')->references(['organization_id', 'id'])->on('threads_accounts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discovered_posts');
    }
};
