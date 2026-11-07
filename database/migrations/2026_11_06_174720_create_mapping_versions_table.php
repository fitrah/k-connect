<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mapping_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('response_mapping_id');
            $table->foreignId('created_by')->nullable();
            $table->unsignedInteger('version');
            $table->json('configuration');
            $table->text('change_summary')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'id']);
            $table->unique(['response_mapping_id', 'version']);
            $table->foreign(['organization_id', 'response_mapping_id'], 'mv_org_mapping_fk')->references(['organization_id', 'id'])->on('response_mappings')->cascadeOnDelete();
            $table->foreign(['organization_id', 'created_by'], 'mv_org_creator_fk')->references(['organization_id', 'id'])->on('users')->deferrable()->initiallyImmediate(false);
            $table->foreign('created_by', 'mv_creator_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapping_versions');
    }
};
