<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('threads_account_id')->nullable()->after('organization_id');
            $table->foreignId('default_response_mapping_id')->nullable()->after('threads_account_id');
            $table->foreign(['organization_id', 'threads_account_id'], 'campaign_organization_account_foreign')
                ->references(['organization_id', 'id'])
                ->on('threads_accounts')
                ->restrictOnDelete();
            $table->foreign(['organization_id', 'default_response_mapping_id'], 'campaign_org_mapping_fk')
                ->references(['organization_id', 'id'])
                ->on('response_mappings')
                ->deferrable()
                ->initiallyImmediate(false);
            $table->foreign('default_response_mapping_id', 'campaign_mapping_fk')
                ->references('id')
                ->on('response_mappings')
                ->nullOnDelete();
        });
        Schema::table('post_classifications', function (Blueprint $table) {
            $table->foreignId('prompt_version_id')->nullable()->after('discovered_post_id');
            $table->foreign(['organization_id', 'prompt_version_id'], 'pc_org_prompt_fk')
                ->references(['organization_id', 'id'])
                ->on('prompt_versions')
                ->deferrable()
                ->initiallyImmediate(false);
            $table->foreign('prompt_version_id', 'pc_prompt_fk')
                ->references('id')
                ->on('prompt_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('post_classifications', function (Blueprint $table) {
            if (DB::getDriverName() === 'sqlite') {
                $table->dropForeign(['prompt_version_id']);
                $table->dropForeign(['organization_id', 'prompt_version_id']);
            } else {
                $table->dropForeign('pc_prompt_fk');
                $table->dropForeign('pc_org_prompt_fk');
            }
        });
        Schema::table('post_classifications', function (Blueprint $table) {
            $table->dropColumn('prompt_version_id');
        });
        Schema::table('campaigns', function (Blueprint $table) {
            if (DB::getDriverName() === 'sqlite') {
                $table->dropForeign(['default_response_mapping_id']);
                $table->dropForeign(['organization_id', 'default_response_mapping_id']);
                $table->dropForeign(['organization_id', 'threads_account_id']);
            } else {
                $table->dropForeign('campaign_mapping_fk');
                $table->dropForeign('campaign_org_mapping_fk');
                $table->dropForeign('campaign_organization_account_foreign');
            }
        });
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['default_response_mapping_id', 'threads_account_id']);
        });
    }
};
