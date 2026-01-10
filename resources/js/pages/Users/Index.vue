<script setup lang="ts">
import { ref } from 'vue';
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
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Search, RotateCcw, Users as UsersIcon } from 'lucide-vue-next';

interface Team {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    teams: Team[];
    created_at: string;
}

interface PaginatedUsers {
    data: User[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
    total: number;
}

interface Props {
    users: PaginatedUsers;
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();

const searchQuery = ref(props.filters.search || '');

const applyFilters = () => {
    router.get(
        '/users',
        {
            search: searchQuery.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const clearFilters = () => {
    searchQuery.value = '';
    router.get('/users');
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Users',
        href: '/users',
    },
];
</script>

<template>
    <Head title="Users Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-7xl p-4">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4">
                <div>
                    <h1 class="text-lg font-semibold tracking-tight">Users Management</h1>
                    <p class="text-sm text-muted-foreground">
                        Manage and view all users in the system.
                    </p>
                </div>
            </div>

            <!-- Summary Cards (Optional, added for consistency) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3 mb-6">
                <Card class="shadow-none p-2 border-slate-200">
                    <CardContent class="p-0 flex items-center gap-3">
                        <div class="p-2 bg-slate-100 rounded-lg">
                            <UsersIcon class="h-5 w-5 text-slate-700" />
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total Users</p>
                            <p class="text-lg font-bold text-slate-900 leading-none mt-1">
                                {{ users.total }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters Card -->
            <Card class="border-none shadow-none py-6 border-b mb-4">
                <CardContent class="p-0">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="space-y-1.5 text-sm">
                            <Label for="search">Search</Label>
                            <div class="relative">
                                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    id="search"
                                    v-model="searchQuery"
                                    placeholder="Search name or email..."
                                    class="h-9 pl-9"
                                    @keyup.enter="applyFilters"
                                />
                            </div>
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

            <!-- Users Table -->
            <Card class="p-0">
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader class="bg-muted">
                                <TableRow>
                                    <TableHead class="py-3">Name</TableHead>
                                    <TableHead class="py-3">Email</TableHead>
                                    <TableHead class="py-3">Role</TableHead>
                                    <TableHead class="py-3">Teams</TableHead>
                                    <TableHead class="py-3">Joined Date</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="users.data.length === 0">
                                    <TableCell colspan="5" class="text-center py-12">
                                        <div class="flex flex-col items-center justify-center text-muted-foreground">
                                            <Search class="h-12 w-12 mb-4 opacity-10" />
                                            <p class="text-lg font-medium">No users found</p>
                                            <p class="text-sm mt-2">Try adjusting your search query</p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="user in users.data" :key="user.id" class="hover:bg-muted/30 border-b last:border-0 transition-colors">
                                    <TableCell class="py-3">
                                        <div class="font-medium text-slate-900">{{ user.name }}</div>
                                    </TableCell>
                                    <TableCell class="py-3 text-slate-600">{{ user.email }}</TableCell>
                                    <TableCell class="py-3 text-xs">
                                        <Badge :variant="user.role === 'admin' ? 'default' : 'secondary'" class="capitalize">
                                            {{ user.role }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="py-3">
                                        <div class="flex flex-wrap gap-1">
                                            <Badge v-for="team in user.teams" :key="team.id" variant="outline" class="text-[10px] bg-slate-50">
                                                {{ team.name }}
                                            </Badge>
                                            <span v-if="user.teams.length === 0" class="text-xs text-muted-foreground italic">No Team</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="py-3 text-slate-600 text-xs text-right">
                                        {{ formatDate(user.created_at) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="users.last_page > 1" class="flex items-center justify-between px-6 py-4 border-t">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ (users.current_page - 1) * 10 + 1 }} to
                            {{ Math.min(users.current_page * 10, users.total) }} of
                            {{ users.total }} users
                        </div>
                        <div class="flex gap-2">
                            <Link
                                v-for="link in users.links"
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
