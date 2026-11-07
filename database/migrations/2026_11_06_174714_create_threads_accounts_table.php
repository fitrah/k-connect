<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('threads_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('threads_user_id');
            $table->string('username');
            $table->text('access_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->enum('status', ['connected', 'expiring', 'expired', 'revoked', 'disconnected'])->default('connected');
            $table->boolean('is_publishing_enabled')->default(false);
            $table->timestamps();
            $table->unique(['organization_id', 'threads_user_id']);
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('threads_accounts');
    }
};
