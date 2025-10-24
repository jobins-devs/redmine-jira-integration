<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\ProjectMapping;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectMappingController extends Controller
{
    /**
     * Display the project mappings page
     */
    public function index()
    {
        $mappings = ProjectMapping::with(['redmineConnection', 'jiraConnection'])->latest()
            ->get();

        $redmineConnections = Connection::redmine()->active()->get();
        $jiraConnections = Connection::jira()->active()->get();

        return Inertia::render('ProjectMappings/Index', [
            'mappings' => $mappings,
            'redmineConnections' => $redmineConnections,
            'jiraConnections' => $jiraConnections,
        ]);
    }

    /**
     * Store a new project mapping
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'redmine_connection_id' => ['required', 'exists:connections,id'],
            'jira_connection_id' => ['required', 'exists:connections,id'],
            'redmine_project_id' => ['required', 'string'],
            'redmine_project_name' => ['required', 'string'],
            'jira_project_key' => ['required', 'string'],
            'jira_project_name' => ['required', 'string'],
            'sync_direction' => ['required', 'in:redmine_to_jira,jira_to_redmine,bidirectional'],
            'is_enabled' => ['boolean'],
            'sync_config' => ['nullable', 'array'],
        ]);

        try {
            ProjectMapping::query()->create($validated);

            return back()->with('success', 'Project mapping created successfully!');
        } catch (QueryException $queryException) {
            if ($queryException->getCode() === '23000') {
                return back()->withErrors(['error' => 'This project mapping already exists.']);
            }

            throw $queryException;
        }
    }

    /**
     * Update a project mapping
     */
    public function update(Request $request, ProjectMapping $projectMapping)
    {
        $validated = $request->validate([
            'sync_direction' => ['sometimes', 'in:redmine_to_jira,jira_to_redmine,bidirectional'],
            'is_enabled' => ['sometimes', 'boolean'],
            'sync_config' => ['nullable', 'array'],
        ]);

        $projectMapping->update($validated);

        return back()->with('success', 'Project mapping updated successfully!');
    }

    /**
     * Delete a project mapping
     */
    public function destroy(ProjectMapping $projectMapping)
    {
        $projectMapping->delete();

        return back()->with('success', 'Project mapping deleted successfully!');
    }

    /**
     * Toggle project mapping enabled status
     */
    public function toggleEnabled(ProjectMapping $projectMapping)
    {
        $projectMapping->update([
            'is_enabled' => ! $projectMapping->is_enabled,
        ]);

        return back()->with('success', 'Project mapping '.($projectMapping->is_enabled ? 'enabled' : 'disabled').' successfully!');
    }
}
