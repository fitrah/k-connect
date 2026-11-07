<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discovered_post_keyword', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discovered_post_id');
            $table->foreignId('keyword_id');
            $table->foreignId('keyword_search_run_id')->nullable();
            $table->timestamp('first_discovered_at');
            $table->unique(['organization_id', 'discovered_post_id', 'keyword_id']);
            $table->foreign(['organization_id', 'discovered_post_id'], 'dpk_org_post_fk')->references(['organization_id', 'id'])->on('discovered_posts')->cascadeOnDelete();
            $table->foreign(['organization_id', 'keyword_id'], 'dpk_org_keyword_fk')->references(['organization_id', 'id'])->on('keywords')->cascadeOnDelete();
            $table->foreign(['organization_id', 'keyword_search_run_id'], 'dpk_org_run_fk')->references(['organization_id', 'id'])->on('keyword_search_runs')->deferrable()->initiallyImmediate(false);
            $table->foreign('keyword_search_run_id', 'dpk_run_fk')->references('id')->on('keyword_search_runs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discovered_post_keyword');
    }
};
