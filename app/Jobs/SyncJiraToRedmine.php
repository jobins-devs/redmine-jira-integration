<?php

namespace App\Jobs;

use App\Models\Connection;
use App\Models\FieldMapping;
use App\Models\ProjectMapping;
use App\Models\SyncLog;
use App\Models\SyncState;
use App\Services\JiraClient;
use App\Services\RedmineClient;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncJiraToRedmine implements ShouldQueue
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
                throw new Exception('Project mapping not found');
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

            // Get latest issue data from Jira
            $jiraIssue = $jiraClient->getIssue($this->syncLog->source_issue_id);

            if (! $jiraIssue) {
                throw new Exception('Jira issue not found');
            }

            // Check if this is a new issue or update
            $syncState = SyncState::query()->where('source_system', 'jira')
                ->where('source_issue_id', $this->syncLog->source_issue_id)
                ->first();

            if ($syncState) {
                // Update existing Redmine issue
                $this->updateRedmineIssue($redmineClient, $syncState, $jiraIssue);
            } else {
                // Create new Redmine issue
                $this->createRedmineIssue($redmineClient, $projectMapping, $jiraIssue);
            }

            $this->syncLog->markAsSuccess();

        } catch (Exception $exception) {
            Log::error('SyncJiraToRedmine failed', [
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
     * Create a new Redmine issue
     */
    protected function createRedmineIssue($redmineClient, $projectMapping, array $jiraIssue): void
    {
        // Map fields from Jira to Redmine
        $issueData = $this->mapJiraToRedmineFields($jiraIssue);

        $result = $redmineClient->createIssue(
            $projectMapping->redmine_project_id,
            $issueData
        );

        if (! $result['success']) {
            throw new Exception('Failed to create Redmine issue: '.json_encode($result['error']));
        }

        $redmineIssue = $result['issue'];

        // Create sync state
        SyncState::query()->create([
            'source_system' => 'jira',
            'target_system' => 'redmine',
            'source_issue_id' => $jiraIssue['key'],
            'target_issue_id' => $redmineIssue['id'],
            'source_updated_at' => $jiraIssue['fields']['updated'],
            'target_updated_at' => $redmineIssue['updated_on'],
            'last_synced_data' => $jiraIssue,
            'last_synced_at' => now(),
        ]);

        $this->syncLog->update(['target_issue_id' => $redmineIssue['id']]);
    }

    /**
     * Update an existing Redmine issue
     */
    protected function updateRedmineIssue($redmineClient, $syncState, array $jiraIssue)
    {
        // Check for conflicts (last-write-wins strategy)
        if ($this->hasConflict($syncState, $jiraIssue)) {
            Log::info('Conflict detected, using last-write-wins', [
                'jira_issue_id' => $jiraIssue['key'],
                'redmine_issue_id' => $syncState->target_issue_id,
            ]);
        }

        // Map fields from Jira to Redmine
        $issueData = $this->mapJiraToRedmineFields($jiraIssue);

        $result = $redmineClient->updateIssue(
            $syncState->target_issue_id,
            $issueData
        );

        if (! $result['success']) {
            throw new Exception('Failed to update Redmine issue: '.json_encode($result['error']));
        }

        // Update sync state
        $syncState->updateSyncState(
            $jiraIssue['fields']['updated'],
            now()->toIso8601String(),
            $jiraIssue
        );

        $this->syncLog->update(['target_issue_id' => $syncState->target_issue_id]);
    }

    /**
     * Map Jira fields to Redmine fields
     */
    protected function mapJiraToRedmineFields(array $jiraIssue): array
    {
        $fields = [
            'subject' => $jiraIssue['fields']['summary'],
            'description' => $jiraIssue['fields']['description'] ?? '',
        ];

        // Map issue type to tracker
        if (isset($jiraIssue['fields']['issuetype']['name'])) {
            $trackerMapping = FieldMapping::getMappingForJira('tracker', $jiraIssue['fields']['issuetype']['name']);
            if ($trackerMapping && $trackerMapping->redmine_id) {
                $fields['tracker_id'] = $trackerMapping->redmine_id;
            }
        }

        // Map priority
        if (isset($jiraIssue['fields']['priority']['name'])) {
            $priorityMapping = FieldMapping::getMappingForJira('priority', $jiraIssue['fields']['priority']['name']);
            if ($priorityMapping && $priorityMapping->redmine_id) {
                $fields['priority_id'] = $priorityMapping->redmine_id;
            }
        }

        // Map assignee
        if (isset($jiraIssue['fields']['assignee']['displayName'])) {
            $userMapping = FieldMapping::getMappingForJira('user', $jiraIssue['fields']['assignee']['displayName']);
            if ($userMapping && $userMapping->redmine_id) {
                $fields['assigned_to_id'] = $userMapping->redmine_id;
            }
        }

        // Map status
        if (isset($jiraIssue['fields']['status']['name'])) {
            $statusMapping = FieldMapping::getMappingForJira('status', $jiraIssue['fields']['status']['name']);
            if ($statusMapping && $statusMapping->redmine_id) {
                $fields['status_id'] = $statusMapping->redmine_id;
            }
        }

        return $fields;
    }

    /**
     * Check if there's a conflict
     */
    protected function hasConflict($syncState, array $jiraIssue): bool
    {
        // Compare update timestamps
        $sourceUpdated = strtotime((string) $jiraIssue['fields']['updated']);
        $lastSynced = strtotime((string) $syncState->source_updated_at);

        return $sourceUpdated <= $lastSynced;
    }
}
