<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Services\JiraClient;
use App\Services\RedmineClient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConnectionController extends Controller
{
    /**
     * Display the connections management page
     */
    public function index()
    {
        $connections = Connection::query()->latest()->get();

        return Inertia::render('Connections/Index', [
            'connections' => $connections,
        ]);
    }

    /**
     * Store a new connection
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:redmine,jira'],
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url'],
            'credentials' => ['required', 'array'],
        ]);

        // Validate credentials based on type
        if ($validated['type'] === 'redmine') {
            $request->validate([
                'credentials.api_key' => ['required', 'string'],
            ]);
        } else {
            $request->validate([
                'credentials.email' => ['required', 'email'],
                'credentials.api_token' => ['required', 'string'],
            ]);
        }

        Connection::query()->create($validated);

        return back()->with('success', 'Connection created successfully!');
    }

    /**
     * Update a connection
     */
    public function update(Request $request, Connection $connection)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'url' => ['sometimes', 'url'],
            'credentials' => ['sometimes', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $connection->update($validated);

        return back()->with('success', 'Connection updated successfully!');
    }

    /**
     * Delete a connection
     */
    public function destroy(Connection $connection)
    {
        $connection->delete();

        return back()->with('success', 'Connection deleted successfully!');
    }

    /**
     * Test a connection
     */
    public function test(Connection $connection)
    {
        $result = $this->testConnectionApi($connection);

        if ($result['success']) {
            $connection->update([
                'connection_status' => 'connected',
                'connection_error' => null,
                'last_tested_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Connection successful!',
                'user' => $result['user'] ?? null,
            ]);
        }

        $connection->update([
            'connection_status' => 'failed',
            'connection_error' => $result['error'],
            'last_tested_at' => now(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Connection failed: '.$result['error'],
        ], 422);
    }

    /**
     * Get projects from a connection
     */
    public function getProjects(Connection $connection)
    {
        $projects = [];

        if ($connection->type === 'redmine') {
            $client = new RedmineClient(
                $connection->url,
                $connection->credentials['api_key']
            );
            $projects = $client->getProjects();
        } else {
            $client = new JiraClient(
                $connection->url,
                $connection->credentials['email'],
                $connection->credentials['api_token']
            );
            $projects = $client->getProjects();
        }

        return response()->json(['projects' => $projects]);
    }

    /**
     * Get field metadata from a connection
     */
    public function getFieldMetadata(Connection $connection)
    {
        $metadata = [];

        if ($connection->type === 'redmine') {
            $client = new RedmineClient(
                $connection->url,
                $connection->credentials['api_key']
            );

            $metadata = [
                'trackers' => $client->getTrackers(),
                'statuses' => $client->getStatuses(),
                'priorities' => $client->getPriorities(),
                'users' => $client->getUsers(),
                'custom_fields' => $client->getCustomFields(),
            ];
        } else {
            $client = new JiraClient(
                $connection->url,
                $connection->credentials['email'],
                $connection->credentials['api_token']
            );

            $metadata = [
                'priorities' => $client->getPriorities(),
                'custom_fields' => $client->getCustomFields(),
            ];
        }

        return response()->json(['metadata' => $metadata]);
    }

    /**
     * Helper to test connection API
     */
    private function testConnectionApi(Connection $connection): array
    {
        if ($connection->type === 'redmine') {
            $client = new RedmineClient(
                $connection->url,
                $connection->credentials['api_key']
            );

            return $client->testConnection();
        }

        $client = new JiraClient(
            $connection->url,
            $connection->credentials['email'],
            $connection->credentials['api_token']
        );

        return $client->testConnection();
    }
}
