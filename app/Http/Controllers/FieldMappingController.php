<?php

namespace App\Http\Controllers;

use App\Models\FieldMapping;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FieldMappingController extends Controller
{
    /**
     * Display the field mappings page
     */
    public function index()
    {
        $mappings = FieldMapping::query()->orderBy('mapping_type')->latest()->get();

        return Inertia::render('FieldMappings/Index', [
            'mappings' => $mappings,
        ]);
    }

    /**
     * Store a new field mapping
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'mapping_type' => ['required', 'in:tracker,status,priority,custom_field,user'],
            'redmine_value' => ['required', 'string'],
            'redmine_id' => ['nullable', 'string'],
            'jira_value' => ['required', 'string'],
            'jira_id' => ['nullable', 'string'],
            'additional_config' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        try {
            FieldMapping::query()->create($validated);

            return back()->with('success', 'Field mapping created successfully!');
        } catch (QueryException $queryException) {
            if ($queryException->getCode() === '23000') {
                return back()->withErrors(['error' => 'This mapping already exists.']);
            }

            throw $queryException;
        }
    }

    /**
     * Update a field mapping
     */
    public function update(Request $request, FieldMapping $fieldMapping)
    {
        $validated = $request->validate([
            'redmine_value' => ['sometimes', 'string'],
            'redmine_id' => ['nullable', 'string'],
            'jira_value' => ['sometimes', 'string'],
            'jira_id' => ['nullable', 'string'],
            'additional_config' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $fieldMapping->update($validated);

        return back()->with('success', 'Field mapping updated successfully!');
    }

    /**
     * Delete a field mapping
     */
    public function destroy(FieldMapping $fieldMapping)
    {
        $fieldMapping->delete();

        return back()->with('success', 'Field mapping deleted successfully!');
    }

    /**
     * Bulk import field mappings
     */
    public function bulkImport(Request $request)
    {
        $validated = $request->validate([
            'mappings' => ['required', 'array'],
            'mappings.*.mapping_type' => ['required', 'in:tracker,status,priority,custom_field,user'],
            'mappings.*.redmine_value' => ['required', 'string'],
            'mappings.*.jira_value' => ['required', 'string'],
        ]);

        $created = 0;
        $errors = [];

        foreach ($validated['mappings'] as $mapping) {
            try {
                FieldMapping::query()->create($mapping);
                $created++;
            } catch (\Exception $e) {
                $errors[] = sprintf('Failed to create mapping for %s: ', $mapping['redmine_value']).$e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'errors' => $errors,
        ]);
    }
}
