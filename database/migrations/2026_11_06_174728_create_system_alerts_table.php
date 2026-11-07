<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('acknowledged_by')->nullable();
            $table->string('type');
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->enum('status', ['open', 'acknowledged', 'resolved'])->default('open');
            $table->string('title');
            $table->text('message');
            $table->string('source_type')->nullable();
            $table->string('source_id')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'status', 'severity']);
            $table->foreign(['organization_id', 'acknowledged_by'], 'sa_org_ack_user_fk')->references(['organization_id', 'id'])->on('users')->deferrable()->initiallyImmediate(false);
            $table->foreign('acknowledged_by', 'sa_ack_user_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_alerts');
    }
};
