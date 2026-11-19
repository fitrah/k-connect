<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'active', 'paused', 'archived'])->default('draft');
            $table->string('default_language', 10)->default('id');
            $table->string('timezone')->default('UTC');
            $table->json('active_hours')->nullable();
            $table->enum('approval_mode', ['human', 'automatic'])->default('human');
            $table->decimal('confidence_threshold', 5, 4)->default(0.8000);
            $table->unsignedInteger('hourly_reply_limit')->default(0);
            $table->unsignedInteger('daily_reply_limit')->default(0);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
