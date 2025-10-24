
<script setup>
import Layout from '@/Components/Layout.vue';
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    mappings: Array,
    redmineConnections: Array,
    jiraConnections: Array,
});

const showCreateModal = ref(false);
const redmineProjects = ref([]);
const jiraProjects = ref([]);
const loadingRedmineProjects = ref(false);
const loadingJiraProjects = ref(false);

const form = useForm({
    redmine_connection_id: '',
    jira_connection_id: '',
    redmine_project_id: '',
    redmine_project_name: '',
    jira_project_key: '',
    jira_project_name: '',
    sync_direction: 'bidirectional',
    is_enabled: true,
});

const loadRedmineProjects = async () => {
    if (!form.redmine_connection_id) return;
    
    loadingRedmineProjects.value = true;
    try {
        const response = await axios.get(`/connections/${form.redmine_connection_id}/projects`);
        redmineProjects.value = response.data.projects;
    } catch (error) {
        alert('Failed to load Redmine projects');
    } finally {
        loadingRedmineProjects.value = false;
    }
};

const loadJiraProjects = async () => {
    if (!form.jira_connection_id) return;
    
    loadingJiraProjects.value = true;
    try {
        const response = await axios.get(`/connections/${form.jira_connection_id}/projects`);
        jiraProjects.value = response.data.projects;
    } catch (error) {
        alert('Failed to load Jira projects');
    } finally {
        loadingJiraProjects.value = false;
    }
};

const selectRedmineProject = (event) => {
    const project = redmineProjects.value.find(p => p.id == event.target.value);
    if (project) {
        form.redmine_project_id = project.id;
        form.redmine_project_name = project.name;
    }
};

const selectJiraProject = (event) => {
    const project = jiraProjects.value.find(p => p.key === event.target.value);
    if (project) {
        form.jira_project_key = project.key;
        form.jira_project_name = project.name;
    }
};

const submit = () => {
    form.post('/project-mappings', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showCreateModal.value = false;
            redmineProjects.value = [];
            jiraProjects.value = [];
        },
    });
};

const deleteMapping = (mapping) => {
    if (confirm('Are you sure you want to delete this project mapping?')) {
        router.delete(`/project-mappings/${mapping.id}`, {
            preserveScroll: true,
        });
    }
};

const toggleEnabled = (mapping) => {
    router.post(`/project-mappings/${mapping.id}/toggle`, {}, {
        preserveScroll: true,
    });
};

const getSyncDirectionIcon = (direction) => {
    const icons = {
        'redmine_to_jira': '🔴 → 🔵',
        'jira_to_redmine': '🔵 → 🔴',
        'bidirectional': '🔴 ⇄ 🔵',
    };
    return icons[direction] || '⇄';
};
</script>

<template>
    <Layout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Project Mappings</h2>
                    <p class="mt-1 text-sm text-gray-600">Configure which projects to sync between Redmine and Jira</p>
                </div>
                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                >
                    ➕ Add Project Mapping
                </button>
            </div>

            <!-- Project Mappings List -->
            <div class="bg-white shadow rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Redmine Project</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Sync Direction</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jira Project</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="mapping in mappings" :key="mapping.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="font-medium">{{ mapping.redmine_project_name }}</div>
                                    <div class="text-gray-500">ID: {{ mapping.redmine_project_id }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                    {{ getSyncDirectionIcon(mapping.sync_direction) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="font-medium">{{ mapping.jira_project_name }}</div>
                                    <div class="text-gray-500">Key: {{ mapping.jira_project_key }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button
                                        @click="toggleEnabled(mapping)"
                                        :class="[
                                            mapping.is_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800',
                                            'px-2 py-1 text-xs font-medium rounded-full cursor-pointer'
                                        ]"
                                    >
                                        {{ mapping.is_enabled ? 'Enabled' : 'Disabled' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button
                                        @click="deleteMapping(mapping)"
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Create Mapping Modal -->
            <div v-if="showCreateModal" class="fixed z-10 inset-0 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCreateModal = false"></div>
                    
                    <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all max-w-2xl w-full z-20">
                        <form @submit.prevent="submit">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Project Mapping</h3>
                                
                                <div class="space-y-4">
                                    <!-- Redmine Connection -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Redmine Connection</label>
                                        <select
                                            v-model="form.redmine_connection_id"
                                            @change="loadRedmineProjects"
                                            required
                                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="">Select a connection</option>
                                            <option v-for="conn in redmineConnections" :key="conn.id" :value="conn.id">
                                                {{ conn.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Redmine Project -->
                                    <div v-if="form.redmine_connection_id">
                                        <label class="block text-sm font-medium text-gray-700">Redmine Project</label>
                                        <select
                                            @change="selectRedmineProject"
                                            required
                                            :disabled="loadingRedmineProjects"
                                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="">{{ loadingRedmineProjects ? 'Loading...' : 'Select a project' }}</option>
                                            <option v-for="project in redmineProjects" :key="project.id" :value="project.id">
                                                {{ project.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Jira Connection -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Jira Connection</label>
                                        <select
                                            v-model="form.jira_connection_id"
                                            @change="loadJiraProjects"
                                            required
                                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="">Select a connection</option>
                                            <option v-for="conn in jiraConnections" :key="conn.id" :value="conn.id">
                                                {{ conn.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Jira Project -->
                                    <div v-if="form.jira_connection_id">
                                        <label class="block text-sm font-medium text-gray-700">Jira Project</label>
                                        <select
                                            @change="selectJiraProject"
                                            required
                                            :disabled="loadingJiraProjects"
                                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="">{{ loadingJiraProjects ? 'Loading...' : 'Select a project' }}</option>
                                            <option v-for="project in jiraProjects" :key="project.key" :value="project.key">
                                                {{ project.name }} ({{ project.key }})
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Sync Direction -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Sync Direction</label>
                                        <select v-model="form.sync_direction" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="bidirectional">Bi-directional (⇄)</option>
                                            <option value="redmine_to_jira">Redmine → Jira</option>
                                            <option value="jira_to_redmine">Jira → Redmine</option>
                                        </select>
                                    </div>

                                    <!-- Enabled -->
                                    <div class="flex items-center">
                                        <input v-model="form.is_enabled" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border border-gray-300 rounded" />
                                        <label class="ml-2 block text-sm text-gray-900">Enable sync immediately</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="px-4 py-3 bg-gray-50 text-right sm:px-6 space-x-2">
                                <button
                                    type="button"
                                    @click="showCreateModal = false"
                                    class="inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                                >
                                    {{ form.processing ? 'Creating...' : 'Create' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>
