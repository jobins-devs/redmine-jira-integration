
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
        Schema::create('field_mappings', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->enum('mapping_type', ['tracker', 'status', 'priority', 'custom_field', 'user'])->index();
            $blueprint->string('redmine_value');
            $blueprint->string('redmine_id')->nullable();
            $blueprint->string('jira_value');
            $blueprint->string('jira_id')->nullable();
            $blueprint->json('additional_config')->nullable(); // For complex mappings
            $blueprint->boolean('is_active')->default(true)->index();
            $blueprint->timestamps();

            // Ensure unique mappings
            $blueprint->unique(['mapping_type', 'redmine_value', 'jira_value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_mappings');
    }
};
