<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncState extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_system',
        'target_system',
        'source_issue_id',
        'target_issue_id',
        'source_updated_at',
        'target_updated_at',
        'last_synced_data',
        'last_synced_at',
    ];

    // Helper methods
    public static function getState($sourceSystem, $sourceIssueId, $targetSystem, $targetIssueId)
    {
        return static::query()->where('source_system', $sourceSystem)
            ->where('source_issue_id', $sourceIssueId)
            ->where('target_system', $targetSystem)
            ->where('target_issue_id', $targetIssueId)
            ->first();
    }

    public static function findBySourceIssue($sourceSystem, $sourceIssueId)
    {
        return static::query()->where('source_system', $sourceSystem)
            ->where('source_issue_id', $sourceIssueId)
            ->get();
    }

    public static function findByTargetIssue($targetSystem, $targetIssueId)
    {
        return static::query()->where('target_system', $targetSystem)
            ->where('target_issue_id', $targetIssueId)
            ->get();
    }

    public function updateSyncState($sourceUpdatedAt, $targetUpdatedAt, $syncedData = null): void
    {
        $this->update([
            'source_updated_at' => $sourceUpdatedAt,
            'target_updated_at' => $targetUpdatedAt,
            'last_synced_data' => $syncedData,
            'last_synced_at' => now(),
        ]);
    }

    protected function casts(): array
    {
        return [
            'last_synced_data' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }
}
