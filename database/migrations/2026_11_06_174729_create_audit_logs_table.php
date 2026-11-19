<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable();
            $table->string('action');
            $table->string('target_type')->nullable();
            $table->string('target_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->uuid('request_id')->nullable();
            $table->uuid('correlation_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'action', 'created_at']);
            $table->index(['target_type', 'target_id']);
            $table->foreign(['organization_id', 'actor_id'], 'al_org_actor_fk')->references(['organization_id', 'id'])->on('users')->deferrable()->initiallyImmediate(false);
            $table->foreign('actor_id', 'al_actor_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
