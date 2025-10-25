
<script setup>
import Layout from '@/Components/Layout.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    recentActivity: Array,
    syncByProject: Array,
    errorLogs: Array,
});

const retrySync = (syncLogId) => {
    if (confirm('Are you sure you want to retry this sync?')) {
        router.post(`/dashboard/sync-logs/${syncLogId}/retry`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                alert('Sync queued for retry!');
            },
        });
    }
};

const getStatusColor = (status) => {
    const colors = {
        success: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
        pending: 'bg-yellow-100 text-yellow-800',
        processing: 'bg-blue-100 text-blue-800',
        retrying: 'bg-purple-100 text-purple-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getSyncTypeIcon = (type) => {
    const icons = {
        create: '➕',
        update: '✏️',
        status_change: '🔄',
    };
    return icons[type] || '📝';
};
</script>

<template>
    <Layout>
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Dashboard</h2>
                <p class="mt-1 text-sm text-gray-600">Monitor sync activity and system status</p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="text-2xl">✅</span>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Synced</dt>
                                    <dd class="text-2xl font-semibold text-gray-900">{{ stats.total_synced }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="text-2xl">⏳</span>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                                    <dd class="text-2xl font-semibold text-gray-900">{{ stats.pending }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="text-2xl">❌</span>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Failed</dt>
                                    <dd class="text-2xl font-semibold text-gray-900">{{ stats.failed }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="text-2xl">📁</span>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Active Mappings</dt>
                                    <dd class="text-2xl font-semibold text-gray-900">{{ stats.active_mappings }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="text-2xl">🔌</span>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Connections</dt>
                                    <dd class="text-2xl font-semibold text-gray-900">{{ stats.total_connections }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Sync Activity</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="activity in recentActivity" :key="activity.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="mr-2">{{ getSyncTypeIcon(activity.sync_type) }}</span>
                                    {{ activity.sync_type }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ activity.source_system }} #{{ activity.source_issue_id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ activity.target_system }} {{ activity.target_issue_id ? '#' + activity.target_issue_id : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[getStatusColor(activity.status), 'px-2 py-1 text-xs font-medium rounded-full']">
                                        {{ activity.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ new Date(activity.created_at).toLocaleString() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <button
                                        v-if="activity.status === 'failed'"
                                        @click="retrySync(activity.id)"
                                        class="text-indigo-600 hover:text-indigo-900"
                                    >
                                        Retry
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Error Logs -->
            <div v-if="errorLogs.length > 0" class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Errors</h3>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <div class="space-y-4">
                        <div v-for="error in errorLogs" :key="error.id" class="border-l-4 border-red-400 bg-red-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <span class="text-red-400">❌</span>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700 font-medium">
                                        {{ error.source_system }} #{{ error.source_issue_id }} → {{ error.target_system }}
                                    </p>
                                    <p class="mt-2 text-sm text-red-600">{{ error.error_message }}</p>
                                    <p class="mt-1 text-xs text-red-500">{{ new Date(error.created_at).toLocaleString() }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>
