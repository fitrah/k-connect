<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('response_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable();
            $table->foreignId('fallback_response_mapping_id')->nullable();
            $table->string('name');
            $table->integer('priority')->default(100);
            $table->enum('status', ['draft', 'active', 'inactive', 'archived'])->default('draft');
            $table->json('applicable_intents')->nullable();
            $table->boolean('human_approval_required')->default(true);
            $table->boolean('auto_reply_allowed')->default(false);
            $table->decimal('confidence_threshold', 5, 4)->default(0.8000);
            $table->timestamps();
            $table->unique(['organization_id', 'id']);
            $table->unique(['organization_id', 'name']);
            $table->foreign(['organization_id', 'campaign_id'], 'rm_org_campaign_fk')->references(['organization_id', 'id'])->on('campaigns')->deferrable()->initiallyImmediate(false);
            $table->foreign('campaign_id', 'rm_campaign_fk')->references('id')->on('campaigns')->nullOnDelete();
            $table->foreign(['organization_id', 'fallback_response_mapping_id'], 'rm_org_fallback_fk')->references(['organization_id', 'id'])->on('response_mappings')->deferrable()->initiallyImmediate(false);
            $table->foreign('fallback_response_mapping_id', 'rm_fallback_fk')->references('id')->on('response_mappings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('response_mappings');
    }
};
