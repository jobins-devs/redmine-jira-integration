<?php

namespace App\Jobs;

use App\Models\Connection;
use App\Models\FieldMapping;
use App\Models\ProjectMapping;
use App\Models\SyncLog;
use App\Models\SyncState;
use App\Services\JiraClient;
use App\Services\RedmineClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncRedmineToJira implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 3;

    public $backoff = [60, 300, 900];

    /**
     * Create a new job instance.
     */
    public function __construct(protected SyncLog $syncLog) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->syncLog->markAsProcessing();

        try {
            /** @var ProjectMapping|null $projectMapping */
            $projectMapping = $this->syncLog->projectMapping;

            if (! $projectMapping) {
                throw new \Exception('Project mapping not found');
            }

            // Initialize clients
            /** @var Connection $redmineConnection */
            $redmineConnection = $projectMapping->redmineConnection;
            /** @var Connection $jiraConnection */
            $jiraConnection = $projectMapping->jiraConnection;

            $redmineClient = new RedmineClient(
                $redmineConnection->url,
                $redmineConnection->credentials['api_key']
            );

            $jiraClient = new JiraClient(
                $jiraConnection->url,
                $jiraConnection->credentials['email'],
                $jiraConnection->credentials['api_token']
            );

            // Get latest issue data from Redmine
            $redmineIssue = $redmineClient->getIssue($this->syncLog->source_issue_id);

            if (! $redmineIssue) {
                throw new \Exception('Redmine issue not found');
            }

            // Check if this is a new issue or update
            $syncState = SyncState::query()->where('source_system', 'redmine')
                ->where('source_issue_id', $this->syncLog->source_issue_id)
                ->first();

            if ($syncState) {
                // Update existing Jira issue
                $this->updateJiraIssue($jiraClient, $syncState, $redmineIssue);
            } else {
                // Create new Jira issue
                $this->createJiraIssue($jiraClient, $projectMapping, $redmineIssue);
            }

            $this->syncLog->markAsSuccess();

        } catch (\Exception $exception) {
            Log::error('SyncRedmineToJira failed', [
                'sync_log_id' => $this->syncLog->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $this->syncLog->markAsFailed($exception->getMessage(), [
                'trace' => $exception->getTraceAsString(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->syncLog->incrementRetry();
                $this->release($this->backoff[$this->attempts() - 1] ?? 900);
            }
        }
    }

    /**
     * Create a new Jira issue
     */
    protected function createJiraIssue($jiraClient, $projectMapping, array $redmineIssue)
    {
        // Map fields from Redmine to Jira
        $issueData = $this->mapRedmineToJiraFields($redmineIssue);

        $result = $jiraClient->createIssue(
            $projectMapping->jira_project_key,
            $issueData
        );

        if (! $result['success']) {
            throw new \Exception('Failed to create Jira issue: '.json_encode($result['error']));
        }

        $jiraIssue = $result['issue'];

        // Create sync state
        SyncState::query()->create([
            'source_system' => 'redmine',
            'target_system' => 'jira',
            'source_issue_id' => $redmineIssue['id'],
            'target_issue_id' => $jiraIssue['key'],
            'source_updated_at' => $redmineIssue['updated_on'],
            'target_updated_at' => now()->toIso8601String(),
            'last_synced_data' => $redmineIssue,
            'last_synced_at' => now(),
        ]);

        $this->syncLog->update(['target_issue_id' => $jiraIssue['key']]);
    }

    /**
     * Update an existing Jira issue
     */
    protected function updateJiraIssue($jiraClient, $syncState, array $redmineIssue)
    {
        // Check for conflicts (last-write-wins strategy)
        if ($this->hasConflict($syncState, $redmineIssue)) {
            Log::info('Conflict detected, using last-write-wins', [
                'redmine_issue_id' => $redmineIssue['id'],
                'jira_issue_id' => $syncState->target_issue_id,
            ]);
        }

        // Map fields from Redmine to Jira
        $issueData = $this->mapRedmineToJiraFields($redmineIssue);

        $result = $jiraClient->updateIssue(
            $syncState->target_issue_id,
            $issueData
        );

        if (! $result['success']) {
            throw new \Exception('Failed to update Jira issue: '.json_encode($result['error']));
        }

        // Update sync state
        $syncState->updateSyncState(
            $redmineIssue['updated_on'],
            now()->toIso8601String(),
            $redmineIssue
        );

        $this->syncLog->update(['target_issue_id' => $syncState->target_issue_id]);
    }

    /**
     * Map Redmine fields to Jira fields
     */
    protected function mapRedmineToJiraFields(array $redmineIssue): array
    {
        $fields = [
            'summary' => $redmineIssue['subject'],
            'description' => $redmineIssue['description'] ?? '',
        ];

        // Map tracker to issue type
        if (isset($redmineIssue['tracker']['name'])) {
            $trackerMapping = FieldMapping::getMappingForRedmine('tracker', $redmineIssue['tracker']['name']);
            if ($trackerMapping) {
                $fields['issuetype'] = ['name' => $trackerMapping->jira_value];
            }
        }

        // Map priority
        if (isset($redmineIssue['priority']['name'])) {
            $priorityMapping = FieldMapping::getMappingForRedmine('priority', $redmineIssue['priority']['name']);
            if ($priorityMapping) {
                $fields['priority'] = ['name' => $priorityMapping->jira_value];
            }
        }

        // Map assignee
        if (isset($redmineIssue['assigned_to']['name'])) {
            $userMapping = FieldMapping::getMappingForRedmine('user', $redmineIssue['assigned_to']['name']);
            if ($userMapping && $userMapping->jira_id) {
                $fields['assignee'] = ['id' => $userMapping->jira_id];
            }
        }

        return $fields;
    }

    /**
     * Check if there's a conflict
     */
    protected function hasConflict($syncState, array $redmineIssue): bool
    {
        // Compare update timestamps
        $sourceUpdated = strtotime((string) $redmineIssue['updated_on']);
        $lastSynced = strtotime((string) $syncState->source_updated_at);

        return $sourceUpdated <= $lastSynced;
    }
}
