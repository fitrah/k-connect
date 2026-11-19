<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable();
            $table->enum('type', ['classification', 'generation']);
            $table->unsignedInteger('version');
            $table->enum('status', ['draft', 'active', 'retired'])->default('draft');
            $table->text('system_instruction');
            $table->text('user_instruction');
            $table->string('model');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'id']);
            $table->unique(['organization_id', 'type', 'version']);
            $table->foreign(['organization_id', 'created_by'], 'pv_org_creator_fk')->references(['organization_id', 'id'])->on('users')->deferrable()->initiallyImmediate(false);
            $table->foreign('created_by', 'pv_creator_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_versions');
    }
};
