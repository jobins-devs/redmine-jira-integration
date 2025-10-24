
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
        Schema::create('project_mappings', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->foreignId('redmine_connection_id')->constrained('connections')->onDelete('cascade');
            $blueprint->foreignId('jira_connection_id')->constrained('connections')->onDelete('cascade');
            $blueprint->string('redmine_project_id');
            $blueprint->string('redmine_project_name');
            $blueprint->string('jira_project_key');
            $blueprint->string('jira_project_name');
            $blueprint->enum('sync_direction', ['redmine_to_jira', 'jira_to_redmine', 'bidirectional'])->default('bidirectional');
            $blueprint->boolean('is_enabled')->default(true)->index();
            $blueprint->json('sync_config')->nullable(); // Additional sync configurations
            $blueprint->timestamps();

            // Ensure unique project pairs
            $blueprint->unique(['redmine_connection_id', 'jira_connection_id', 'redmine_project_id', 'jira_project_key'], 'unique_project_mapping');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_mappings');
    }
};
