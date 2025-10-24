<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_mapping_id',
        'source_system',
        'target_system',
        'source_issue_id',
        'target_issue_id',
        'sync_type',
        'status',
        'retry_count',
        'error_message',
        'error_details',
        'sync_data',
        'processed_at',
    ];

    // Relationships
    public function projectMapping(): BelongsTo
    {
        return $this->belongsTo(ProjectMapping::class);
    }

    // Scopes
    protected function scopePending($query): Builder
    {
        return $query->where('status', 'pending');
    }

    protected function scopeFailed($query): Builder
    {
        return $query->where('status', 'failed');
    }

    protected function scopeSuccess($query): Builder
    {
        return $query->where('status', 'success');
    }

    protected function scopeRecent($query, $limit = 50): Builder
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    // Helper methods
    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsSuccess($targetIssueId = null): void
    {
        $this->update([
            'status' => 'success',
            'target_issue_id' => $targetIssueId ?? $this->target_issue_id,
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed($errorMessage, $errorDetails = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'error_details' => $errorDetails,
            'processed_at' => now(),
        ]);
    }

    public function incrementRetry(): void
    {
        $this->increment('retry_count');
        $this->update(['status' => 'retrying']);
    }

    protected function casts(): array
    {
        return [
            'error_details' => 'array',
            'sync_data' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
