<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RedmineClient
{
    protected string $baseUrl;

    public function __construct($baseUrl, protected $apiKey)
    {
        $this->baseUrl = rtrim((string) $baseUrl, '/');
    }

    /**
     * Test the connection to Redmine
     */
    public function testConnection(): array
    {
        try {
            $response = Http::withHeaders([
                'X-Redmine-API-Key' => $this->apiKey,
            ])->get($this->baseUrl.'/users/current.json');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'user' => $response->json('user'),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('errors') ?? 'Connection failed',
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
            $response = Http::withHeaders([
                'X-Redmine-API-Key' => $this->apiKey,
            ])->get($this->baseUrl.'/projects.json');

            if ($response->successful()) {
                return $response->json('projects');
            }

            return [];
        } catch (\Exception $exception) {
            Log::error('Redmine getProjects error: '.$exception->getMessage());

            return [];
        }
    }

    /**
     * Get a single issue
     */
    public function getIssue($issueId)
    {
        try {
            $response = Http::withHeaders([
                'X-Redmine-API-Key' => $this->apiKey,
            ])->get(sprintf('%s/issues/%s.json', $this->baseUrl, $issueId), [
                'include' => 'attachments,relations,journals',
            ]);

            if ($response->successful()) {
                return $response->json('issue');
            }

            return;
        } catch (\Exception $exception) {
            Log::error('Redmine getIssue error: '.$exception->getMessage());

            return;
        }
    }

    /**
     * Create a new issue
     */
    public function createIssue($projectId, array $issueData): array
    {
        try {
            $response = Http::withHeaders([
                'X-Redmine-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/issues.json', [
                'issue' => array_merge([
                    'project_id' => $projectId,
                ], $issueData),
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'issue' => $response->json('issue'),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('errors') ?? 'Failed to create issue',
            ];
        } catch (\Exception $exception) {
            Log::error('Redmine createIssue error: '.$exception->getMessage());

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Update an issue
     */
    public function updateIssue($issueId, array $issueData): array
    {
        try {
            $response = Http::withHeaders([
                'X-Redmine-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->put(sprintf('%s/issues/%s.json', $this->baseUrl, $issueId), [
                'issue' => $issueData,
            ]);

            if ($response->successful() || $response->status() === 204) {
                return [
                    'success' => true,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('errors') ?? 'Failed to update issue',
            ];
        } catch (\Exception $exception) {
            Log::error('Redmine updateIssue error: '.$exception->getMessage());

            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Get trackers
     */
    public function getTrackers(): array
    {
        try {
            $response = Http::withHeaders([
                'X-Redmine-API-Key' => $this->apiKey,
            ])->get($this->baseUrl.'/trackers.json');

            if ($response->successful()) {
                return $response->json('trackers');
            }

            return [];
        } catch (\Exception $exception) {
            Log::error('Redmine getTrackers error: '.$exception->getMessage());

            return [];
        }
    }

    /**
     * Get issue statuses
     */
    public function getStatuses(): array
    {
        try {
            $response = Http::withHeaders([
                'X-Redmine-API-Key' => $this->apiKey,
            ])->get($this->baseUrl.'/issue_statuses.json');

            if ($response->successful()) {
                return $response->json('issue_statuses');
            }

            return [];
        } catch (\Exception $exception) {
            Log::error('Redmine getStatuses error: '.$exception->getMessage());

            return [];
        }
    }

    /**
     * Get priorities
     */
    public function getPriorities(): array
    {
        try {
            $response = Http::withHeaders([
                'X-Redmine-API-Key' => $this->apiKey,
            ])->get($this->baseUrl.'/enumerations/issue_priorities.json');

            if ($response->successful()) {
                return $response->json('issue_priorities');
            }

            return [];
        } catch (\Exception $exception) {
            Log::error('Redmine getPriorities error: '.$exception->getMessage());

            return [];
        }
    }

    /**
     * Get users
     */
    public function getUsers(): array
    {
        try {
            $response = Http::withHeaders([
                'X-Redmine-API-Key' => $this->apiKey,
            ])->get($this->baseUrl.'/users.json');

            if ($response->successful()) {
                return $response->json('users');
            }

            return [];
        } catch (\Exception $exception) {
            Log::error('Redmine getUsers error: '.$exception->getMessage());

            return [];
        }
    }

    /**
     * Get custom fields
     */
    public function getCustomFields(): array
    {
        try {
            $response = Http::withHeaders([
                'X-Redmine-API-Key' => $this->apiKey,
            ])->get($this->baseUrl.'/custom_fields.json');

            if ($response->successful()) {
                return $response->json('custom_fields');
            }

            return [];
        } catch (\Exception $exception) {
            Log::error('Redmine getCustomFields error: '.$exception->getMessage());

            return [];
        }
    }
}
