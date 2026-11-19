<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quota_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('threads_account_id')->nullable();
            $table->foreignId('campaign_id')->nullable();
            $table->foreignId('keyword_id')->nullable();
            $table->string('operation');
            $table->timestamp('window_started_at');
            $table->timestamp('window_ends_at');
            $table->unsignedInteger('used')->default(0);
            $table->unsignedInteger('limit')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'operation', 'window_ends_at']);
            $table->foreign(['organization_id', 'threads_account_id'], 'qu_org_account_fk')->references(['organization_id', 'id'])->on('threads_accounts')->deferrable()->initiallyImmediate(false);
            $table->foreign('threads_account_id', 'qu_account_fk')->references('id')->on('threads_accounts')->nullOnDelete();
            $table->foreign(['organization_id', 'campaign_id'], 'qu_org_campaign_fk')->references(['organization_id', 'id'])->on('campaigns')->deferrable()->initiallyImmediate(false);
            $table->foreign('campaign_id', 'qu_campaign_fk')->references('id')->on('campaigns')->nullOnDelete();
            $table->foreign(['organization_id', 'keyword_id'], 'qu_org_keyword_fk')->references(['organization_id', 'id'])->on('keywords')->deferrable()->initiallyImmediate(false);
            $table->foreign('keyword_id', 'qu_keyword_fk')->references('id')->on('keywords')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quota_usages');
    }
};
