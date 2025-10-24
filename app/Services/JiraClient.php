<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JiraClient
{
    protected string $baseUrl;

    public function __construct($baseUrl, protected $email, protected $apiToken)
    {
        $this->baseUrl = rtrim((string) $baseUrl, '/');
    }

    /**
     * Test the connection to Jira
     */
    public function testConnection(): array
    {
        try {
            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->get($this->baseUrl.'/rest/api/3/myself');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'user' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('errorMessages') ?? 'Connection failed',
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Get all projects
     */
    public function getProjects(): array
    {
        try {
            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->get($this->baseUrl.'/rest/api/3/project');

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (\Exception $exception) {
            Log::error('Jira getProjects error: '.$exception->getMessage());

            return [];
        }
    }

    /**
     * Get a single issue
     */
    public function getIssue($issueKey)
    {
        try {
            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->get(sprintf('%s/rest/api/3/issue/%s', $this->baseUrl, $issueKey), [
                    'expand' => 'changelog,renderedFields',
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return;
        } catch (\Exception $exception) {
            Log::error('Jira getIssue error: '.$exception->getMessage());

            return;
        }
    }

    /**
     * Create a new issue
     */
    public function createIssue($projectKey, array $issueData): array
    {
        try {
            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->post($this->baseUrl.'/rest/api/3/issue', [
                    'fields' => array_merge([
                        'project' => ['key' => $projectKey],
                    ], $issueData),
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'issue' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('errors') ?? $response->json('errorMessages') ?? 'Failed to create issue',
            ];
        } catch (\Exception $exception) {
            Log::error('Jira createIssue error: '.$exception->getMessage());

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Update an issue
     */
    public function updateIssue($issueKey, array $issueData): array
    {
        try {
            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->put(sprintf('%s/rest/api/3/issue/%s', $this->baseUrl, $issueKey), [
                    'fields' => $issueData,
                ]);

            if ($response->successful() || $response->status() === 204) {
                return [
                    'success' => true,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('errors') ?? $response->json('errorMessages') ?? 'Failed to update issue',
            ];
        } catch (\Exception $exception) {
            Log::error('Jira updateIssue error: '.$exception->getMessage());

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Transition an issue (change status)
     */
    public function transitionIssue($issueKey, $transitionId, array $fields = []): array
    {
        try {
            $payload = ['transition' => ['id' => $transitionId]];

            if ($fields !== []) {
                $payload['fields'] = $fields;
            }

            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->post(sprintf('%s/rest/api/3/issue/%s/transitions', $this->baseUrl, $issueKey), $payload);

            if ($response->successful() || $response->status() === 204) {
                return [
                    'success' => true,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('errors') ?? $response->json('errorMessages') ?? 'Failed to transition issue',
            ];
        } catch (\Exception $exception) {
            Log::error('Jira transitionIssue error: '.$exception->getMessage());

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Get available transitions for an issue
     */
    public function getTransitions($issueKey): array
    {
        try {
            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->get(sprintf('%s/rest/api/3/issue/%s/transitions', $this->baseUrl, $issueKey));

            if ($response->successful()) {
                return $response->json('transitions');
            }

            return [];
        } catch (\Exception $exception) {
            Log::error('Jira getTransitions error: '.$exception->getMessage());

            return [];
        }
    }

    /**
     * Get issue types for a project
     */
    public function getIssueTypes($projectKey): array
    {
        try {
            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->get(sprintf('%s/rest/api/3/project/%s', $this->baseUrl, $projectKey));

            if ($response->successful()) {
                return $response->json('issueTypes') ?? [];
            }

            return [];
        } catch (\Exception $exception) {
            Log::error('Jira getIssueTypes error: '.$exception->getMessage());

            return [];
        }
    }

    /**
     * Get priorities
     */
    public function getPriorities(): array
    {
        try {
            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->get($this->baseUrl.'/rest/api/3/priority');

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (\Exception $exception) {
            Log::error('Jira getPriorities error: '.$exception->getMessage());

            return [];
        }
    }

    /**
     * Get users (assignable users for a project)
     */
    public function getUsers($projectKey): array
    {
        try {
            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->get($this->baseUrl.'/rest/api/3/user/assignable/search', [
                    'project' => $projectKey,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (\Exception $exception) {
            Log::error('Jira getUsers error: '.$exception->getMessage());

            return [];
        }
    }

    /**
     * Get custom fields
     */
    public function getCustomFields(): array
    {
        try {
            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->get($this->baseUrl.'/rest/api/3/field');

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (\Exception $exception) {
            Log::error('Jira getCustomFields error: '.$exception->getMessage());

            return [];
        }
    }

    /**
     * Search for issues using JQL
     */
    public function searchIssues($jql, $startAt = 0, $maxResults = 50)
    {
        try {
            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->post($this->baseUrl.'/rest/api/3/search', [
                    'jql' => $jql,
                    'startAt' => $startAt,
                    'maxResults' => $maxResults,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return;
        } catch (\Exception $exception) {
            Log::error('Jira searchIssues error: '.$exception->getMessage());

            return;
        }
    }
}
