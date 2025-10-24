
<script setup>
import Layout from '@/Components/Layout.vue';
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    connections: Array,
});

const showCreateModal = ref(false);
const testingConnection = ref(null);

const form = useForm({
    type: 'redmine',
    name: '',
    url: '',
    credentials: {
        api_key: '',
        email: '',
        api_token: '',
    },
});

const submit = () => {
    form.post('/connections', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showCreateModal.value = false;
        },
    });
};

const testConnection = async (connection) => {
    testingConnection.value = connection.id;
    
    try {
        const response = await axios.post(`/connections/${connection.id}/test`);
        alert('Connection successful!');
        router.reload({ only: ['connections'] });
    } catch (error) {
        alert('Connection failed: ' + (error.response?.data?.message || error.message));
    } finally {
        testingConnection.value = null;
    }
};

const deleteConnection = (connection) => {
    if (confirm('Are you sure you want to delete this connection?')) {
        router.delete(`/connections/${connection.id}`, {
            preserveScroll: true,
        });
    }
};

const getStatusColor = (status) => {
    const colors = {
        connected: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
        not_tested: 'bg-gray-100 text-gray-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getTypeIcon = (type) => {
    return type === 'redmine' ? '🔴' : '🔵';
};
</script>

<template>
    <Layout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Connections</h2>
                    <p class="mt-1 text-sm text-gray-600">Manage Redmine and Jira connections</p>
                </div>
                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                >
                    ➕ Add Connection
                </button>
            </div>

            <!-- Connections List -->
            <div class="bg-white shadow rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Tested</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="connection in connections" :key="connection.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="mr-2">{{ getTypeIcon(connection.type) }}</span>
                                    {{ connection.type }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ connection.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ connection.url }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[getStatusColor(connection.connection_status), 'px-2 py-1 text-xs font-medium rounded-full']">
                                        {{ connection.connection_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ connection.last_tested_at ? new Date(connection.last_tested_at).toLocaleString() : 'Never' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button
                                        @click="testConnection(connection)"
                                        :disabled="testingConnection === connection.id"
                                        class="text-indigo-600 hover:text-indigo-900"
                                    >
                                        {{ testingConnection === connection.id ? 'Testing...' : 'Test' }}
                                    </button>
                                    <button
                                        @click="deleteConnection(connection)"
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

            <!-- Create Connection Modal -->
            <div v-if="showCreateModal" class="fixed z-10 inset-0 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCreateModal = false"></div>
                    
                    <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all max-w-lg w-full z-20">
                        <form @submit.prevent="submit">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Connection</h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Type</label>
                                        <select v-model="form.type" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="redmine">Redmine</option>
                                            <option value="jira">Jira</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Name</label>
                                        <input v-model="form.name" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">URL</label>
                                        <input v-model="form.url" type="url" required placeholder="https://..." class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    </div>

                                    <div v-if="form.type === 'redmine'">
                                        <label class="block text-sm font-medium text-gray-700">API Key</label>
                                        <input v-model="form.credentials.api_key" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                    </div>

                                    <div v-if="form.type === 'jira'">
                                        <label class="block text-sm font-medium text-gray-700">Email</label>
                                        <input v-model="form.credentials.email" type="email" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />

                                        <label class="block text-sm font-medium text-gray-700 mt-4">API Token</label>
                                        <input v-model="form.credentials.api_token" type="password" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
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
