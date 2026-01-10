<script setup lang="ts">
import { computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, Save, Users, ImageIcon } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';

const form = useForm({
    name: '',
    slug: '',
    description: '',
    logo: null as File | null,
});

// Auto-generate slug from name
watch(() => form.name, (newName) => {
    form.slug = newName
        .toLowerCase()
        .replace(/[^\w ]+/g, '')
        .replace(/ +/g, '-');
});

const submit = () => {
    form.post('/teams', {
        preserveScroll: true,
        forceFormData: true, // Required for file upload
    });
};

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Teams', href: '/teams' },
    { title: 'Create Team', href: '/teams/create' }
];
</script>

<template>
    <Head title="Create Team" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-6 px-4 max-w-2xl">
            <!-- Header -->
            <div class="mb-6">
                <Link href="/teams" class="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-3">
                    <ArrowLeft class="mr-1.5 h-3.5 w-3.5" />
                    Back to Teams
                </Link>
                <h1 class="text-2xl font-bold tracking-tight">Create New Team</h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Set up a new team to collaborate with others.
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Team Information</CardTitle>
                    <CardDescription>
                        Provide the basic details for your team.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Name -->
                        <div class="space-y-2">
                            <Label for="name">Team Name <span class="text-red-500">*</span></Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="e.g. Engineering Team"
                                :class="{ 'border-red-500': form.errors.name }"
                                required
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <!-- Slug -->
                        <div class="space-y-2">
                            <Label for="slug">Team Slug <span class="text-red-500">*</span></Label>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-muted-foreground">/</span>
                                <Input
                                    id="slug"
                                    v-model="form.slug"
                                    placeholder="engineering-team"
                                    :class="{ 'border-red-500': form.errors.slug }"
                                    required
                                />
                            </div>
                            <InputError :message="form.errors.slug" />
                            <p class="text-xs text-muted-foreground">
                                This will be used in the team's URL.
                            </p>
                        </div>

                        <!-- Logo -->
                        <div class="space-y-2">
                            <Label for="logo">Team Logo</Label>
                            <div class="flex items-center gap-4">
                                <div class="h-16 w-16 rounded-lg bg-slate-100 flex items-center justify-center border overflow-hidden">
                                    <Users class="h-8 w-8 text-slate-400" />
                                </div>
                                <div class="flex-1">
                                    <Input
                                        id="logo"
                                        type="file"
                                        accept="image/*"
                                        @input="form.logo = ($event.target as HTMLInputElement).files?.[0] || null"
                                        class="h-9 px-3 py-1"
                                    />
                                    <p class="text-xs text-muted-foreground mt-1">
                                        Max size 2MB. Format: JPG, PNG, GIF.
                                    </p>
                                </div>
                            </div>
                            <InputError :message="form.errors.logo" />
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <Label for="description">Description</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="What is this team about?"
                                rows="4"
                                :class="{ 'border-red-500': form.errors.description }"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-3 pt-4 border-t">
                            <Button type="submit" :disabled="form.processing">
                                <Save class="mr-2 h-4 w-4" />
                                {{ form.processing ? 'Creating...' : 'Create Team' }}
                            </Button>
                            <Link href="/teams">
                                <Button type="button" variant="outline">Cancel</Button>
                            </Link>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
