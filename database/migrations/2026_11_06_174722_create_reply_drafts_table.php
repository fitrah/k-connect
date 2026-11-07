<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reply_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discovered_post_id');
            $table->foreignId('post_classification_id');
            $table->foreignId('mapping_version_id');
            $table->foreignId('prompt_version_id');
            $table->foreignId('reviewed_by')->nullable();
            $table->enum('status', ['generated', 'pending_review', 'edited', 'approved', 'rejected', 'publishing', 'published', 'failed', 'cancelled'])->default('generated');
            $table->text('content');
            $table->string('language', 10)->nullable();
            $table->json('warnings')->nullable();
            $table->boolean('requires_human_review')->default(true);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'status', 'created_at']);
            $table->foreign(['organization_id', 'discovered_post_id'], 'rd_org_post_fk')->references(['organization_id', 'id'])->on('discovered_posts')->cascadeOnDelete();
            $table->foreign(['organization_id', 'post_classification_id'], 'rd_org_classification_fk')->references(['organization_id', 'id'])->on('post_classifications')->restrictOnDelete();
            $table->foreign(['organization_id', 'mapping_version_id'], 'rd_org_mapping_version_fk')->references(['organization_id', 'id'])->on('mapping_versions')->restrictOnDelete();
            $table->foreign(['organization_id', 'prompt_version_id'], 'rd_org_prompt_fk')->references(['organization_id', 'id'])->on('prompt_versions')->restrictOnDelete();
            $table->foreign(['organization_id', 'reviewed_by'], 'rd_org_reviewer_fk')->references(['organization_id', 'id'])->on('users')->deferrable()->initiallyImmediate(false);
            $table->foreign('reviewed_by', 'rd_reviewer_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reply_drafts');
    }
};
