
<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const navigation = [
    { name: 'Dashboard', href: '/', icon: '📊' },
    { name: 'Connections', href: '/connections', icon: '🔌' },
    { name: 'Field Mappings', href: '/field-mappings', icon: '🔗' },
    { name: 'Project Mappings', href: '/project-mappings', icon: '📁' },
];

const mobileMenuOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <h1 class="text-xl font-bold text-gray-900">Redmine Jira Integration</h1>
                        </div>
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <Link
                                v-for="item in navigation"
                                :key="item.name"
                                :href="item.href"
                                :class="[
                                    $page.url === item.href
                                        ? 'border-indigo-500 text-gray-900'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                                    'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium'
                                ]"
                            >
                                <span class="mr-2">{{ item.icon }}</span>
                                {{ item.name }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div v-if="mobileMenuOpen" class="sm:hidden">
                <div class="pt-2 pb-3 space-y-1">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            $page.url === item.href
                                ? 'bg-indigo-50 border-indigo-500 text-indigo-700'
                                : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700',
                            'block pl-3 pr-4 py-2 border-l-4 text-base font-medium'
                        ]"
                    >
                        <span class="mr-2">{{ item.icon }}</span>
                        {{ item.name }}
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>
    </div>
</template>
