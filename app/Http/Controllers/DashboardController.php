<?php

namespace App\Http\Controllers;

use App\Jobs\SyncJiraToRedmine;
use App\Jobs\SyncRedmineToJira;
use App\Models\Connection;
use App\Models\ProjectMapping;
use App\Models\SyncLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the dashboard
     */
    public function index()
    {
        // Get statistics
        $stats = [
            'total_synced' => SyncLog::success()->count(),
            'pending' => SyncLog::pending()->count(),
            'failed' => SyncLog::failed()->count(),
            'active_mappings' => ProjectMapping::enabled()->count(),
            'total_connections' => Connection::active()->count(),
        ];

        // Get recent sync activity
        $recentActivity = SyncLog::with('projectMapping')->latest()
            ->limit(20)
            ->get();

        // Get sync stats by project
        $syncByProject = SyncLog::query()->select('project_mapping_id', DB::raw('count(*) as total'), DB::raw('sum(case when status = "success" then 1 else 0 end) as success'), DB::raw('sum(case when status = "failed" then 1 else 0 end) as failed'), DB::raw('sum(case when status = "pending" then 1 else 0 end) as pending'))
            ->whereNotNull('project_mapping_id')
            ->groupBy('project_mapping_id')
            ->with('projectMapping')
            ->get();

        // Get error logs (recent failures)
        $errorLogs = SyncLog::failed()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'recentActivity' => $recentActivity,
            'syncByProject' => $syncByProject,
            'errorLogs' => $errorLogs,
        ]);
    }

    /**
     * Get sync statistics for charts
     */
    public function getStats(Request $request)
    {
        $days = $request->input('days', 7);

        $stats = SyncLog::query()->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'), DB::raw('sum(case when status = "success" then 1 else 0 end) as success'), DB::raw('sum(case when status = "failed" then 1 else 0 end) as failed'))
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json(['stats' => $stats]);
    }

    /**
     * Retry a failed sync
     */
    public function retrySyncLog(SyncLog $syncLog)
    {
        if ($syncLog->status !== 'failed') {
            return response()->json([
                'success' => false,
                'message' => 'Only failed syncs can be retried.',
            ], 422);
        }

        // Reset the sync log to pending and queue it
        $syncLog->update([
            'status' => 'pending',
            'error_message' => null,
            'error_details' => null,
        ]);

        // Dispatch the appropriate job based on source system
        if ($syncLog->source_system === 'redmine') {
            dispatch(new SyncRedmineToJira($syncLog));
        } else {
            dispatch(new SyncJiraToRedmine($syncLog));
        }

        return response()->json([
            'success' => true,
            'message' => 'Sync queued for retry.',
        ]);
    }
}
