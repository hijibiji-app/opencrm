<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ArrowLeft, Save } from 'lucide-vue-next';

const form = useForm({
    name: '',
    platform: '',
    category: '',
    domain: '',
    status: 'active',
    description: '',
});

const submit = () => {
    form.post('/projects', {
        preserveScroll: true,
    });
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
        title: 'Create',
        href: '/projects/create',
    },
];

// Predefined options
const platforms = ['Laravel & Vue JS', 'Laravel & Nuxt JS', 'Laravel & Livewire','Laravel & React JS','Laravel & Next JS', 'Laravel & Blade', 'WordPress', 'React', 'Next.js', 'Vue.js', 'Custom PHP', 'Node.js', 'Other'];
const categories = ['LMS', 'Ecommerce', 'Single Vendor', 'Multi Vendor', 'Portfolio', 'Blog', 'CRM', 'SaaS', 'Other'];
const statuses = ['active', 'inactive', 'maintenance'];
</script>

<template>
    <Head title="Create Project" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-4xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-lg font-semibold tracking-tight">Create New Project</h1>
                    <p class="text-sm text-muted-foreground">
                        Add a new project to your portfolio
                    </p>
                </div>
                <Link :href="'/projects'">
                    <Button variant="outline" size="sm">
                        <ArrowLeft class="mr-1.5 h-4 w-4" />
                        Back
                    </Button>
                </Link>
            </div>

            <!-- Form Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Project Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div class="space-y-2">
                                <Label for="name">Project Name <span class="text-red-500">*</span></Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="Enter project name"
                                    :class="{ 'border-red-500': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                            </div>

                            <!-- Platform -->
                            <div class="space-y-2">
                                <Label for="platform">Platform <span class="text-red-500">*</span></Label>
                                <Select v-model="form.platform">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.platform }">
                                        <SelectValue placeholder="Select platform" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="platform in platforms" :key="platform" :value="platform">
                                            {{ platform }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.platform" class="text-xs text-red-500">{{ form.errors.platform }}</p>
                            </div>

                            <!-- Category -->
                            <div class="space-y-2">
                                <Label for="category">Category <span class="text-red-500">*</span></Label>
                                <Select v-model="form.category">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.category }">
                                        <SelectValue placeholder="Select category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="category in categories" :key="category" :value="category">
                                            {{ category }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.category" class="text-xs text-red-500">{{ form.errors.category }}</p>
                            </div>

                            <!-- Status -->
                            <div class="space-y-2">
                                <Label for="status">Status <span class="text-red-500">*</span></Label>
                                <Select v-model="form.status">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.status }">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="status in statuses" :key="status" :value="status" class="capitalize">
                                            {{ status }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="text-xs text-red-500">{{ form.errors.status }}</p>
                            </div>

                            <!-- Domain -->
                            <div class="space-y-2 md:col-span-2">
                                <Label for="domain">Domain / URL</Label>
                                <Input
                                    id="domain"
                                    v-model="form.domain"
                                    placeholder="https://example.com"
                                    type="url"
                                    :class="{ 'border-red-500': form.errors.domain }"
                                />
                                <p v-if="form.errors.domain" class="text-xs text-red-500">{{ form.errors.domain }}</p>
                            </div>

                            <!-- Description -->
                            <div class="space-y-2 md:col-span-2">
                                <Label for="description">Description</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Brief description of the project"
                                    rows="4"
                                    :class="{ 'border-red-500': form.errors.description }"
                                />
                                <p v-if="form.errors.description" class="text-xs text-red-500">{{ form.errors.description }}</p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t">
                            <Link :href="'/projects'">
                                <Button type="button" variant="outline">
                                    Cancel
                                </Button>
                            </Link>
                            <Button type="submit" :disabled="form.processing">
                                <Save class="mr-1.5 h-4 w-4" />
                                {{ form.processing ? 'Creating...' : 'Create Project' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
