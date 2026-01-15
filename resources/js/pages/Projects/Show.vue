<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ArrowLeft, Pencil, ExternalLink, Calendar, User, Folder, Globe } from 'lucide-vue-next';

interface ProjectUser {
    id: number;
    name: string;
}

interface Project {
    id: number;
    name: string;
    platform: string;
    category: string;
    domain?: string;
    status: string;
    description?: string;
    user: ProjectUser;
    created_at: string;
}

const props = defineProps<{
    project: Project;
}>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'active':
            return 'default';
        case 'inactive':
            return 'destructive';
        case 'maintenance':
            return 'secondary';
        default:
            return 'secondary';
    }
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
        title: props.project.name,
        href: `/projects/${props.project.id}`,
    },
];
</script>

<template>
    <Head :title="project.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-4xl p-4">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
                <div class="flex items-center gap-4">
                    <Link :href="'/projects'">
                        <Button variant="ghost" size="icon" class="rounded-full">
                            <ArrowLeft class="h-5 w-5" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ project.name }}</h1>
                        <div class="flex items-center gap-2 mt-1">
                            <Badge :variant="getStatusVariant(project.status)" class="capitalize">
                                {{ project.status }}
                            </Badge>
                            <span class="text-sm text-muted-foreground">• {{ project.category }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="`/projects/${project.id}/edit`">
                        <Button variant="outline" size="sm">
                            <Pencil class="mr-1.5 h-4 w-4" />
                            Edit Project
                        </Button>
                    </Link>
                    <a v-if="project.domain" :href="project.domain" target="_blank">
                        <Button size="sm">
                            <ExternalLink class="mr-1.5 h-4 w-4" />
                            Visit Site
                        </Button>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Main Info -->
                <div class="md:col-span-2 space-y-6">
                    <Card class="shadow-sm">
                        <CardHeader>
                            <CardTitle class="text-lg flex items-center gap-2">
                                <Folder class="h-5 w-5 text-primary" />
                                Project Description
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-slate-600 whitespace-pre-wrap leading-relaxed">
                                {{ project.description || 'No description provided for this project.' }}
                            </p>
                        </CardContent>
                    </Card>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <Card class="shadow-sm border-slate-100">
                            <CardContent class="p-4 flex items-center gap-3">
                                <div class="p-2 bg-blue-50 rounded-lg">
                                    <Globe class="h-5 w-5 text-blue-600" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Platform</p>
                                    <p class="text-base font-semibold text-slate-900">{{ project.platform }}</p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card class="shadow-sm border-slate-100">
                            <CardContent class="p-4 flex items-center gap-3">
                                <div class="p-2 bg-purple-50 rounded-lg">
                                    <Calendar class="h-5 w-5 text-purple-600" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Added On</p>
                                    <p class="text-base font-semibold text-slate-900">{{ formatDate(project.created_at) }}</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Sidebar Info -->
                <div class="space-y-6">
                    <Card class="shadow-sm">
                        <CardHeader>
                            <CardTitle class="text-base">Details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-start gap-3">
                                <User class="h-5 w-5 text-muted-foreground mt-0.5" />
                                <div>
                                    <p class="text-xs text-muted-foreground">Created By</p>
                                    <p class="text-sm font-medium">{{ project.user.name }}</p>
                                </div>
                            </div>

                            <div v-if="project.domain" class="flex items-start gap-3">
                                <Globe class="h-5 w-5 text-muted-foreground mt-0.5" />
                                <div class="overflow-hidden">
                                    <p class="text-xs text-muted-foreground">Domain</p>
                                    <a :href="project.domain" target="_blank" class="text-sm font-medium text-blue-600 hover:underline truncate block">
                                        {{ project.domain }}
                                    </a>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
