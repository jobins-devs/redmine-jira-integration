<?php

namespace App\Http\Controllers;

use App\Jobs\SyncJiraToRedmine;
use App\Jobs\SyncRedmineToJira;
use App\Models\ProjectMapping;
use App\Models\SyncLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle Redmine webhook
     */
    public function redmine(Request $request)
    {
        // Verify webhook signature if configured
        if (config('services.redmine.webhook_secret')) {
            $signature = $request->header('X-Redmine-Signature');
            if (! $this->verifyRedmineSignature($request->getContent(), $signature)) {
                Log::warning('Invalid Redmine webhook signature');

                return response()->json(['error' => 'Invalid signature'], 403);
            }
        }

        $payload = $request->all();

        Log::info('Redmine webhook received', ['payload' => $payload]);

        // Extract issue data
        $action = $payload['action'] ?? null;
        $issue = $payload['issue'] ?? null;

        if (! $issue) {
            return response()->json(['error' => 'No issue data'], 400);
        }

        $projectId = $issue['project']['id'] ?? null;
        $issueId = $issue['id'] ?? null;

        if (! $projectId || ! $issueId) {
            return response()->json(['error' => 'Missing project or issue ID'], 400);
        }

        // Find project mapping
        $projectMapping = ProjectMapping::query()->where('redmine_project_id', $projectId)
            ->enabled()
            ->first();

        if (! $projectMapping || ! $projectMapping->canSyncFromRedmine()) {
            Log::info('No project mapping found or sync not allowed', [
                'project_id' => $projectId,
                'issue_id' => $issueId,
            ]);

            return response()->json(['message' => 'Sync not configured'], 200);
        }

        // Check if this is a duplicate webhook (idempotency)
        $existingLog = SyncLog::query()->where('source_system', 'redmine')
            ->where('source_issue_id', $issueId)
            ->where('status', 'pending')
            ->where('created_at', '>', now()->subMinutes(5))
            ->first();

        if ($existingLog) {
            Log::info('Duplicate webhook detected, skipping', ['issue_id' => $issueId]);

            return response()->json(['message' => 'Already processing'], 200);
        }

        // Determine sync type
        $syncType = $this->determineSyncType($action, $issue);

        // Create sync log
        $syncLog = SyncLog::query()->create([
            'project_mapping_id' => $projectMapping->id,
            'source_system' => 'redmine',
            'target_system' => 'jira',
            'source_issue_id' => $issueId,
            'sync_type' => $syncType,
            'status' => 'pending',
            'sync_data' => $issue,
        ]);

        // Dispatch sync job
        dispatch(new SyncRedmineToJira($syncLog));

        return response()->json(['message' => 'Webhook received and queued'], 200);
    }

    /**
     * Handle Jira webhook
     */
    public function jira(Request $request)
    {
        // Verify webhook signature if configured
        if (config('services.jira.webhook_secret')) {
            $signature = $request->header('X-Hub-Signature');
            if (! $this->verifyJiraSignature($request->getContent(), $signature)) {
                Log::warning('Invalid Jira webhook signature');

                return response()->json(['error' => 'Invalid signature'], 403);
            }
        }

        $payload = $request->all();

        Log::info('Jira webhook received', ['event' => $payload['webhookEvent'] ?? 'unknown']);

        $webhookEvent = $payload['webhookEvent'] ?? null;
        $issue = $payload['issue'] ?? null;

        if (! $issue) {
            return response()->json(['error' => 'No issue data'], 400);
        }

        $projectKey = $issue['fields']['project']['key'] ?? null;
        $issueKey = $issue['key'] ?? null;

        if (! $projectKey || ! $issueKey) {
            return response()->json(['error' => 'Missing project or issue key'], 400);
        }

        // Find project mapping
        $projectMapping = ProjectMapping::query()->where('jira_project_key', $projectKey)
            ->enabled()
            ->first();

        if (! $projectMapping || ! $projectMapping->canSyncFromJira()) {
            Log::info('No project mapping found or sync not allowed', [
                'project_key' => $projectKey,
                'issue_key' => $issueKey,
            ]);

            return response()->json(['message' => 'Sync not configured'], 200);
        }

        // Check if this is a duplicate webhook (idempotency)
        $existingLog = SyncLog::query()->where('source_system', 'jira')
            ->where('source_issue_id', $issueKey)
            ->where('status', 'pending')
            ->where('created_at', '>', now()->subMinutes(5))
            ->first();

        if ($existingLog) {
            Log::info('Duplicate webhook detected, skipping', ['issue_key' => $issueKey]);

            return response()->json(['message' => 'Already processing'], 200);
        }

        // Determine sync type based on webhook event
        $syncType = $this->determineJiraSyncType($webhookEvent, $payload);

        // Create sync log
        $syncLog = SyncLog::query()->create([
            'project_mapping_id' => $projectMapping->id,
            'source_system' => 'jira',
            'target_system' => 'redmine',
            'source_issue_id' => $issueKey,
            'sync_type' => $syncType,
            'status' => 'pending',
            'sync_data' => $issue,
        ]);

        // Dispatch sync job
        dispatch(new SyncJiraToRedmine($syncLog));

        return response()->json(['message' => 'Webhook received and queued'], 200);
    }

    /**
     * Verify Redmine webhook signature
     */
    private function verifyRedmineSignature($payload, $signature): bool
    {
        $secret = config('services.redmine.webhook_secret');
        $computed = hash_hmac('sha256', (string) $payload, (string) $secret);

        return hash_equals($computed, $signature);
    }

    /**
     * Verify Jira webhook signature
     */
    private function verifyJiraSignature($payload, $signature): bool
    {
        $secret = config('services.jira.webhook_secret');
        $computed = 'sha256='.hash_hmac('sha256', (string) $payload, (string) $secret);

        return hash_equals($computed, $signature);
    }

    /**
     * Determine sync type from Redmine action
     */
    private function determineSyncType($action, array $issue): string
    {
        if ($action === 'opened' || $action === 'created') {
            return 'create';
        }

        // Check if status changed
        if (isset($issue['status'])) {
            return 'status_change';
        }

        return 'update';
    }

    /**
     * Determine sync type from Jira webhook event
     */
    private function determineJiraSyncType($webhookEvent, array $payload): string
    {
        if ($webhookEvent === 'jira:issue_created') {
            return 'create';
        }

        if ($webhookEvent === 'jira:issue_updated') {
            // Check if it's a status change
            $changelog = $payload['changelog'] ?? null;
            if ($changelog) {
                foreach ($changelog['items'] ?? [] as $item) {
                    if ($item['field'] === 'status') {
                        return 'status_change';
                    }
                }
            }

            return 'update';
        }

        return 'update';
    }
}
