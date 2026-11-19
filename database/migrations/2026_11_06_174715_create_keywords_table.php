<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id');
            $table->foreignId('response_mapping_id')->nullable();
            $table->string('query');
            $table->enum('search_mode', ['KEYWORD', 'TAG'])->default('KEYWORD');
            $table->enum('search_type', ['RECENT', 'TOP'])->default('RECENT');
            $table->enum('media_type', ['all', 'text', 'image', 'video'])->default('all');
            $table->string('author_username')->nullable();
            $table->json('include_terms')->nullable();
            $table->json('exclude_terms')->nullable();
            $table->string('language', 10)->nullable();
            $table->unsignedInteger('interval_minutes')->default(15);
            $table->unsignedInteger('priority')->default(100);
            $table->json('active_hours')->nullable();
            $table->timestamp('last_searched_at')->nullable();
            $table->timestamp('next_search_at')->nullable();
            $table->enum('status', ['draft', 'active', 'paused', 'quota_paused', 'error', 'archived'])->default('draft');
            $table->timestamps();
            $table->unique(['organization_id', 'id']);
            $table->unique(['campaign_id', 'search_mode', 'query']);
            $table->index(['organization_id', 'status', 'next_search_at']);
            $table->foreign(['organization_id', 'campaign_id'], 'kw_org_campaign_fk')->references(['organization_id', 'id'])->on('campaigns')->cascadeOnDelete();
            $table->foreign(['organization_id', 'response_mapping_id'], 'kw_org_mapping_fk')->references(['organization_id', 'id'])->on('response_mappings')->deferrable()->initiallyImmediate(false);
            $table->foreign('response_mapping_id', 'kw_mapping_fk')->references('id')->on('response_mappings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keywords');
    }
};
