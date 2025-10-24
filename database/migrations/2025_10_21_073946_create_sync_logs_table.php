
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
        Schema::create('sync_logs', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->foreignId('project_mapping_id')->nullable()->constrained('project_mappings')->onDelete('cascade');
            $blueprint->enum('source_system', ['redmine', 'jira'])->index();
            $blueprint->enum('target_system', ['redmine', 'jira'])->index();
            $blueprint->string('source_issue_id');
            $blueprint->string('target_issue_id')->nullable();
            $blueprint->enum('sync_type', ['create', 'update', 'status_change'])->index();
            $blueprint->enum('status', ['pending', 'processing', 'success', 'failed', 'retrying'])->default('pending')->index();
            $blueprint->integer('retry_count')->default(0);
            $blueprint->text('error_message')->nullable();
            $blueprint->json('error_details')->nullable();
            $blueprint->json('sync_data')->nullable(); // Payload data
            $blueprint->timestamp('processed_at')->nullable();
            $blueprint->timestamps();

            // Indexes for performance
            $blueprint->index(['source_system', 'source_issue_id']);
            $blueprint->index(['target_system', 'target_issue_id']);
            $blueprint->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
