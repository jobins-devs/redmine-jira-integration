<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'mapping_type',
        'redmine_value',
        'redmine_id',
        'jira_value',
        'jira_id',
        'additional_config',
        'is_active',
    ];

    // Scopes
    protected function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected function scopeByType($query, $type)
    {
        return $query->where('mapping_type', $type);
    }

    // Helper methods
    public static function getMappingForRedmine($type, $redmineValue)
    {
        return static::active()
            ->byType($type)
            ->where('redmine_value', $redmineValue)
            ->first();
    }

    public static function getMappingForJira($type, $jiraValue)
    {
        return static::active()
            ->byType($type)
            ->where('jira_value', $jiraValue)
            ->first();
    }

    protected function casts(): array
    {
        return [
            'additional_config' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
