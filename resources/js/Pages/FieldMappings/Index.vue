
<script setup>
import Layout from '@/Components/Layout.vue';
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    mappings: Array,
});

const showCreateModal = ref(false);

const form = useForm({
    mapping_type: 'tracker',
    redmine_value: '',
    redmine_id: '',
    jira_value: '',
    jira_id: '',
    is_active: true,
});

const submit = () => {
    form.post('/field-mappings', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showCreateModal.value = false;
        },
    });
};

const deleteMapping = (mapping) => {
    if (confirm('Are you sure you want to delete this mapping?')) {
        router.delete(`/field-mappings/${mapping.id}`, {
            preserveScroll: true,
        });
    }
};

const getMappingTypeIcon = (type) => {
    const icons = {
        tracker: '📋',
        status: '📊',
        priority: '⚡',
        custom_field: '⚙️',
        user: '👤',
    };
    return icons[type] || '🔗';
};

const groupedMappings = props.mappings.reduce((acc, mapping) => {
    if (!acc[mapping.mapping_type]) {
        acc[mapping.mapping_type] = [];
    }
    acc[mapping.mapping_type].push(mapping);
    return acc;
}, {});
</script>

<template>
    <Layout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Field Mappings</h2>
                    <p class="mt-1 text-sm text-gray-600">Map fields between Redmine and Jira</p>
                </div>
                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                >
                    ➕ Add Mapping
                </button>
            </div>

            <!-- Mappings by Type -->
            <div v-for="(items, type) in groupedMappings" :key="type" class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        <span class="mr-2">{{ getMappingTypeIcon(type) }}</span>
                        {{ type.replace('_', ' ').toUpperCase() }} Mappings
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Redmine Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Redmine ID</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">→</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jira Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jira ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="mapping in items" :key="mapping.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ mapping.redmine_value }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ mapping.redmine_id || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-400">
                                    ⇄
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ mapping.jira_value }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ mapping.jira_id || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[mapping.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800', 'px-2 py-1 text-xs font-medium rounded-full']">
                                        {{ mapping.is_active ? 'Active' : 'Inactive' }}
                                    </span>
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
                    
                    <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all max-w-lg w-full z-20">
                        <form @submit.prevent="submit">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Field Mapping</h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Mapping Type</label>
                                        <select v-model="form.mapping_type" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="tracker">Tracker (Issue Type)</option>
                                            <option value="status">Status</option>
                                            <option value="priority">Priority</option>
                                            <option value="custom_field">Custom Field</option>
                                            <option value="user">User</option>
                                        </select>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Redmine Value</label>
                                            <input v-model="form.redmine_value" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Redmine ID</label>
                                            <input v-model="form.redmine_id" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Jira Value</label>
                                            <input v-model="form.jira_value" type="text" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Jira ID</label>
                                            <input v-model="form.jira_id" type="text" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                        </div>
                                    </div>

                                    <div class="flex items-center">
                                        <input v-model="form.is_active" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border border-gray-300 rounded" />
                                        <label class="ml-2 block text-sm text-gray-900">Active</label>
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
