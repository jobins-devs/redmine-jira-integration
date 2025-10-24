
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
        Schema::create('connections', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->enum('type', ['redmine', 'jira'])->index();
            $blueprint->string('name');
            $blueprint->string('url');
            $blueprint->text('credentials'); // Encrypted JSON: {api_key} or {email, api_token}
            $blueprint->boolean('is_active')->default(true)->index();
            $blueprint->timestamp('last_tested_at')->nullable();
            $blueprint->enum('connection_status', ['connected', 'failed', 'not_tested'])->default('not_tested');
            $blueprint->text('connection_error')->nullable();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connections');
    }
};
