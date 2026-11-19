<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keyword_search_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('keyword_id');
            $table->enum('status', ['pending', 'running', 'succeeded', 'failed', 'skipped'])->default('pending');
            $table->json('request_metadata')->nullable();
            $table->unsignedInteger('result_count')->default(0);
            $table->unsignedInteger('new_post_count')->default(0);
            $table->unsignedInteger('quota_estimate')->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'keyword_id', 'started_at']);
            $table->foreign(['organization_id', 'keyword_id'], 'ksr_org_keyword_fk')->references(['organization_id', 'id'])->on('keywords')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keyword_search_runs');
    }
};
