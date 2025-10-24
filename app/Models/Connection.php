<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Connection extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'url',
        'credentials',
        'is_active',
        'last_tested_at',
        'connection_status',
        'connection_error',
    ];

    // Encrypt credentials when setting
    protected function setCredentialsAttribute($value): void
    {
        $this->attributes['credentials'] = Crypt::encryptString(json_encode($value));
    }

    // Decrypt credentials when getting
    protected function getCredentialsAttribute($value): mixed
    {
        return json_decode(Crypt::decryptString($value), true);
    }

    // Relationships
    public function projectMappingsAsRedmine()
    {
        return $this->hasMany(ProjectMapping::class, 'redmine_connection_id');
    }

    public function projectMappingsAsJira()
    {
        return $this->hasMany(ProjectMapping::class, 'jira_connection_id');
    }

    // Scopes
    protected function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected function scopeRedmine($query)
    {
        return $query->where('type', 'redmine');
    }

    protected function scopeJira($query)
    {
        return $query->where('type', 'jira');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }
}
