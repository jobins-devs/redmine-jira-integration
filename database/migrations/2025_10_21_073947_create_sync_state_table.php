
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sync_state', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->enum('source_system', ['redmine', 'jira'])->index();
            $blueprint->enum('target_system', ['redmine', 'jira'])->index();
            $blueprint->string('source_issue_id')->index();
            $blueprint->string('target_issue_id')->index();
            $blueprint->string('source_updated_at');
            $blueprint->string('target_updated_at');
            $blueprint->json('last_synced_data')->nullable(); // Store last synced state for conflict detection
            $blueprint->timestamp('last_synced_at');
            $blueprint->timestamps();

            // Ensure unique mapping between issues
            $blueprint->unique(['source_system', 'source_issue_id', 'target_system', 'target_issue_id'], 'unique_sync_state');

            // Indexes for quick lookups
            $blueprint->index(['source_system', 'source_issue_id']);
            $blueprint->index(['target_system', 'target_issue_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_state');
    }
};
