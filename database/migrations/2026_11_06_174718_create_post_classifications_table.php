<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_classifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discovered_post_id');
            $table->boolean('is_relevant');
            $table->decimal('confidence', 5, 4);
            $table->string('intent')->nullable();
            $table->string('language', 10)->nullable();
            $table->string('sentiment')->nullable();
            $table->json('risk_flags')->nullable();
            $table->text('reason')->nullable();
            $table->string('recommended_action')->nullable();
            $table->string('model');
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->json('raw_output')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'is_relevant', 'intent']);
            $table->foreign(['organization_id', 'discovered_post_id'], 'pc_org_post_fk')->references(['organization_id', 'id'])->on('discovered_posts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_classifications');
    }
};
