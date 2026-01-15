<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Search, RotateCcw, Folder as FolderIcon, Plus, MoreVertical, Pencil, Trash2, ExternalLink } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';

interface User {
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
    user: User;
    created_at: string;
}

interface PaginatedProjects {
    data: Project[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
    total: number;
}

interface Props {
    projects: PaginatedProjects;
    filters: {
        search?: string;
        platform?: string;
        category?: string;
        status?: string;
    };
    platforms: string[];
    categories: string[];
}

const props = defineProps<Props>();

const searchQuery = ref(props.filters.search || '');
const selectedPlatform = ref(props.filters.platform || 'none');
const selectedCategory = ref(props.filters.category || 'none');
const selectedStatus = ref(props.filters.status || 'none');

const applyFilters = () => {
    router.get(
        '/projects',
        {
            search: searchQuery.value || undefined,
            platform: selectedPlatform.value !== 'none' ? selectedPlatform.value : undefined,
            category: selectedCategory.value !== 'none' ? selectedCategory.value : undefined,
            status: selectedStatus.value !== 'none' ? selectedStatus.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedPlatform.value = 'none';
    selectedCategory.value = 'none';
    selectedStatus.value = 'none';
    router.get('/projects');
};

const deleteProject = (projectId: number) => {
    if (confirm('Are you sure you want to delete this project?')) {
        router.delete(`/projects/${projectId}`, {
            preserveScroll: true,
        });
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
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
];
</script>

<template>
    <Head title="Projects Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-7xl p-4">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb">
                <div>
                    <h1 class="text-lg font-semibold tracking-tight">Projects Management</h1>
                    <p class="text-sm text-muted-foreground">
                        Manage and track all client projects.
                    </p>
                </div>
                <div class="flex items-center gap-2 mt-3 md:mt-0">
                    <Link :href="'/projects/domains'">
                        <Button variant="outline" size="sm">
                            <ExternalLink class="mr-1.5 h-4 w-4" />
                            Domain List
                        </Button>
                    </Link>
                    <Link :href="'/projects/create'">
                        <Button size="sm">
                            <Plus class="mr-1.5 h-4 w-4" />
                            Add Project
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Summary Card -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                <Card class="shadow-none p-2 border-slate-200">
                    <CardContent class="p-0 flex items-center gap-3">
                        <div class="p-2 bg-slate-100 rounded-lg">
                            <FolderIcon class="h-5 w-5 text-slate-700" />
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total Projects</p>
                            <p class="text-lg font-bold text-slate-900 leading-none mt-1">
                                {{ projects.total }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters Card -->
            <Card class="border-none shadow-none py-6 border-b">
                <CardContent class="p-0">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div class="space-y-1.5 text-sm">
                            <Label for="search">Search</Label>
                            <div class="relative">
                                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    id="search"
                                    v-model="searchQuery"
                                    placeholder="Search name or domain..."
                                    class="h-9 pl-9"
                                    @keyup.enter="applyFilters"
                                />
                            </div>
                        </div>

                        <div class="space-y-1.5 text-sm">
                            <Label for="platform">Platform</Label>
                            <Select v-model="selectedPlatform">
                                <SelectTrigger class="h-9 w-full">
                                    <SelectValue placeholder="All Platforms" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">All Platforms</SelectItem>
                                    <SelectItem v-for="platform in platforms" :key="platform" :value="platform">
                                        {{ platform }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1.5 text-sm">
                            <Label for="category">Category</Label>
                            <Select v-model="selectedCategory">
                                <SelectTrigger class="h-9 w-full">
                                    <SelectValue placeholder="All Categories" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">All Categories</SelectItem>
                                    <SelectItem v-for="category in categories" :key="category" :value="category">
                                        {{ category }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1.5 text-sm">
                            <Label for="status">Status</Label>
                            <Select v-model="selectedStatus">
                                <SelectTrigger class="h-9 w-full">
                                    <SelectValue placeholder="All Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">All Status</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                    <SelectItem value="maintenance">Maintenance</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex items-end gap-2">
                            <Button size="sm" @click="applyFilters" class="h-9 px-4">
                                <Search class="mr-1.5 h-3.5 w-3.5" />
                                Search
                            </Button>
                            <Button size="sm" variant="outline" @click="clearFilters" class="h-9 px-3">
                                <RotateCcw class="mr-1.5 h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Projects Table -->
            <Card class="p-0">
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader class="bg-muted">
                                <TableRow>
                                    <TableHead class="py-3">Name</TableHead>
                                    <TableHead class="py-3">Platform</TableHead>
                                    <TableHead class="py-3">Category</TableHead>
                                    <TableHead class="py-3">Domain</TableHead>
                                    <TableHead class="py-3">Status</TableHead>
                                    <TableHead class="py-3">Created By</TableHead>
                                    <TableHead class="py-3">Created At</TableHead>
                                    <TableHead class="py-3 text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="projects.data.length === 0">
                                    <TableCell colspan="8" class="text-center py-12">
                                        <div class="flex flex-col items-center justify-center text-muted-foreground">
                                            <Search class="h-12 w-12 mb-4 opacity-10" />
                                            <p class="text-lg font-medium">No projects found</p>
                                            <p class="text-sm mt-2">Try adjusting your search or filters</p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="project in projects.data" :key="project.id" class="hover:bg-muted/30 border-b last:border-0 transition-colors">
                                    <TableCell class="py-3">
                                        <div class="font-medium">{{ project.name }}</div>
                                    </TableCell>
                                    <TableCell class="py-2">
                                        <Badge variant="outline" class="bg-blue-50 text-blue-700 border-blue-200">
                                            {{ project.platform }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="py-2">
                                        <Badge variant="outline" class="bg-purple-50 text-purple-700 border-purple-200">
                                            {{ project.category }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="py-2">
                                        <a v-if="project.domain" :href="project.domain" target="_blank" class="hover:text-blue-600 underline">
                                            {{ project.domain }}
                                        </a>
                                        <span v-else class="text-muted-foreground italic">No domain</span>
                                    </TableCell>
                                    <TableCell class="py-2">
                                        <Badge :variant="getStatusVariant(project.status)" class="capitalize">
                                            {{ project.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="py-2">
                                        {{ project.user.name }}
                                    </TableCell>
                                    <TableCell class="py-2">
                                        {{ formatDate(project.created_at) }}
                                    </TableCell>
                                    <TableCell class="py-2 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                                    <MoreVertical class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem as-child>
                                                    <Link :href="`/projects/${project.id}/edit`" class="cursor-pointer">
                                                        <Pencil class="mr-2 h-4 w-4" />
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem @click="deleteProject(project.id)" class="text-red-600">
                                                    <Trash2 class="mr-2 h-4 w-4" />
                                                    Delete
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="projects.last_page > 1" class="flex items-center justify-between px-6 py-4 border-t">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ (projects.current_page - 1) * 10 + 1 }} to
                            {{ Math.min(projects.current_page * 10, projects.total) }} of
                            {{ projects.total }} projects
                        </div>
                        <div class="flex gap-2">
                            <Link
                                v-for="link in projects.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 rounded text-sm',
                                    link.active
                                        ? 'bg-primary text-primary-foreground'
                                        : 'hover:bg-muted',
                                    !link.url && 'opacity-50 cursor-not-allowed',
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
