<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->date('metric_date');
            $table->foreignId('campaign_id')->nullable();
            $table->foreignId('keyword_id')->nullable();
            $table->foreignId('response_mapping_id')->nullable();
            $table->foreignId('threads_account_id')->nullable();
            foreach (['searches', 'discovered_posts', 'relevant_posts', 'drafts_generated', 'approved_drafts', 'rejected_drafts', 'published_replies', 'failed_replies', 'llm_input_tokens', 'llm_output_tokens'] as $column) {
                $table->unsignedBigInteger($column)->default(0);
            }
            $table->decimal('estimated_cost', 14, 6)->default(0);
            $table->timestamps();
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'metric_date']);
            $table->foreign(['organization_id', 'campaign_id'], 'dm_org_campaign_fk')->references(['organization_id', 'id'])->on('campaigns')->deferrable()->initiallyImmediate(false);
            $table->foreign('campaign_id', 'dm_campaign_fk')->references('id')->on('campaigns')->nullOnDelete();
            $table->foreign(['organization_id', 'keyword_id'], 'dm_org_keyword_fk')->references(['organization_id', 'id'])->on('keywords')->deferrable()->initiallyImmediate(false);
            $table->foreign('keyword_id', 'dm_keyword_fk')->references('id')->on('keywords')->nullOnDelete();
            $table->foreign(['organization_id', 'response_mapping_id'], 'dm_org_mapping_fk')->references(['organization_id', 'id'])->on('response_mappings')->deferrable()->initiallyImmediate(false);
            $table->foreign('response_mapping_id', 'dm_mapping_fk')->references('id')->on('response_mappings')->nullOnDelete();
            $table->foreign(['organization_id', 'threads_account_id'], 'dm_org_account_fk')->references(['organization_id', 'id'])->on('threads_accounts')->deferrable()->initiallyImmediate(false);
            $table->foreign('threads_account_id', 'dm_account_fk')->references('id')->on('threads_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_metrics');
    }
};
