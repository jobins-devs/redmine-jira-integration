<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'redmine_connection_id',
        'jira_connection_id',
        'redmine_project_id',
        'redmine_project_name',
        'jira_project_key',
        'jira_project_name',
        'sync_direction',
        'is_enabled',
        'sync_config',
    ];

    // Relationships
    public function redmineConnection(): BelongsTo
    {
        return $this->belongsTo(Connection::class, 'redmine_connection_id');
    }

    public function jiraConnection(): BelongsTo
    {
        return $this->belongsTo(Connection::class, 'jira_connection_id');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }

    // Scopes
    protected function scopeEnabled($query): Builder
    {
        return $query->where('is_enabled', true);
    }

    // Helper methods
    public function canSyncFromRedmine(): bool
    {
        return in_array($this->sync_direction, ['redmine_to_jira', 'bidirectional']);
    }

    public function canSyncFromJira(): bool
    {
        return in_array($this->sync_direction, ['jira_to_redmine', 'bidirectional']);
    }

    protected function casts(): array
    {
        return [
            'sync_config' => 'array',
            'is_enabled' => 'boolean',
        ];
    }
}
