<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Copy, Check, ExternalLink, ArrowLeft } from 'lucide-vue-next';
import { ref } from 'vue';

interface Project {
    id: number;
    name: string;
    platform: string;
    category: string;
    domain: string;
}

interface Props {
    groupedProjects: Record<string, Record<string, Project[]>>;
}

const props = defineProps<Props>();

const copiedSection = ref<string | null>(null);

const copyToClipboard = (text: string, sectionId: string) => {
    navigator.clipboard.writeText(text).then(() => {
        copiedSection.value = sectionId;
        setTimeout(() => {
            copiedSection.value = null;
        }, 2000);
    });
};

const copyAllDomains = (projects: Project[], sectionId: string) => {
    const domains = projects.map(p => p.domain).join('\n');
    copyToClipboard(domains, sectionId);
};

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Projects',
        href: '/projects',
    },
    {
        title: 'Domain List',
        href: '/projects/domains',
    },
];
</script>

<template>
    <Head title="Project Domains" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-7xl p-4">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">Project Domain Directory</h1>
                    <p class="text-sm text-muted-foreground">
                        Quickly browse and copy project links grouped by platform and category.
                    </p>
                </div>
                <div class="flex items-center gap-2 mt-4 md:mt-0">
                    <Link href="/projects">
                        <Button variant="outline" size="sm">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Management
                        </Button>
                    </Link>
                </div>
            </div>

            <div v-if="Object.keys(groupedProjects).length === 0" class="py-12 text-center">
                <Card class="max-w-md mx-auto">
                    <CardContent class="pt-10 pb-10">
                        <div class="flex flex-col items-center">
                            <ExternalLink class="h-12 w-12 text-muted-foreground opacity-20 mb-4" />
                            <h3 class="text-lg font-medium text-slate-900">No Domains Found</h3>
                            <p class="text-sm text-muted-foreground mt-2">
                                Projects must have a domain URL entered to appear here.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Platforms -->
            <div v-for="(categories, platform) in groupedProjects" :key="platform" class="mb-5">
                <div class="flex items-center gap-3 mb-4">
                    <Badge variant="secondary" class="text-base px-3 py-0.5 bg-gray-50 text-black border-black-100">
                        {{ platform }}
                    </Badge>
                    <div class="h-px flex-1 bg-gray-200"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Categories -->
                    <div v-for="(projects, category) in categories" :key="category">
                        <Card class="h-full">
                            <CardHeader class="pb-3 flex flex-row items-center justify-between space-y-0">
                                <div>
                                    <CardTitle class="text-base font-semibold">{{ category }}</CardTitle>
                                    <p class="text-xs text-muted-foreground mt-1">{{ projects.length }} projects</p>
                                </div>
                                <Button 
                                    variant="ghost" 
                                    size="sm" 
                                    class="h-8 px-2 text-xs" 
                                    @click="copyAllDomains(projects, `${platform}-${category}`)"
                                >
                                    <template v-if="copiedSection === `${platform}-${category}`">
                                        <Check class="h-3.5 w-3.5 mr-1 text-green-600" />
                                        Copied
                                    </template>
                                    <template v-else>
                                        <Copy class="h-3.5 w-3.5 mr-1" />
                                        Copy All
                                    </template>
                                </Button>
                            </CardHeader>
                            <CardContent>
                                <ul class="space-y-2">
                                    <li v-for="project in projects" :key="project.id" class="flex items-center justify-between group">
                                        <div class="flex flex-col min-w-0">
                                            <span class="text-sm font-medium text-slate-900 truncate">{{ project.name }}</span>
                                            <a :href="project.domain" target="_blank" class="text-xs text-blue-600 hover:underline truncate">
                                                {{ project.domain.replace(/^https?:\/\//, '') }}
                                            </a>
                                        </div>
                                        <Button 
                                            variant="ghost" 
                                            size="icon" 
                                            class="h-7 w-7 opacity-0 group-hover:opacity-100 transition-opacity" 
                                            @click="copyToClipboard(project.domain, project.id.toString())"
                                        >
                                            <template v-if="copiedSection === project.id.toString()">
                                                <Check class="h-3.5 w-3.5 text-green-600" />
                                            </template>
                                            <template v-else>
                                                <Copy class="h-3.5 w-3.5" />
                                            </template>
                                        </Button>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
