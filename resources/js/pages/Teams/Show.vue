<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { 
    Users, 
    ArrowLeft, 
    Edit2, 
    Mail, 
    Shield, 
    User, 
    Calendar,
    Globe,
    UserPlus,
    Trash2,
    ShieldCheck,
    MoreVertical
} from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface Member {
    id: number;
    name: string;
    email: string;
    profile_photo_url: string;
    pivot?: {
        role: string;
    };
}

interface Team {
    id: number;
    name: string;
    slug: string;
    logo: string | null;
    description: string | null;
    owner: Member;
    members: Member[];
    members_count: number;
    created_at: string;
    can_manage_members: boolean;
}

const props = defineProps<{
    team: { data: Team };
}>();

const team = props.team.data;

const addMemberForm = useForm({
    email: '',
    role: 'member',
});

const isAddMemberOpen = ref(false);

const addMember = () => {
    addMemberForm.post(`/teams/${team.id}/members`, {
        onSuccess: () => {
            isAddMemberOpen.value = false;
            addMemberForm.reset();
        },
    });
};

const updateRole = (member: Member, role: any) => {
    router.put(`/teams/${team.id}/members/${member.id}`, { role: role as string }, {
        preserveScroll: true,
    });
};

const removeMember = (member: Member) => {
    if (confirm(`Are you sure you want to remove ${member.name} from the team?`)) {
        router.delete(`/teams/${team.id}/members/${member.id}`, {
            preserveScroll: true,
        });
    }
};

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Teams', href: '/teams' },
    { title: team.name, href: `/teams/${team.id}` }
];
</script>

<template>
    <Head :title="team.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-6 px-4 max-w-5xl">
            <!-- Flash Messages -->
            <Alert v-if="($page.props.flash as any)?.success" class="mb-6 border-green-500 bg-green-50">
                <AlertDescription class="text-green-800">
                    {{ ($page.props.flash as any).success }}
                </AlertDescription>
            </Alert>

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6 mb-8">
                <div class="flex gap-4">
                    <div class="h-20 w-20 rounded-xl bg-slate-100 flex items-center justify-center border shadow-sm overflow-hidden shrink-0">
                        <img v-if="team.logo" :src="team.logo" class="h-full w-full object-cover" />
                        <Users v-else class="h-10 w-10 text-slate-400" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <h1 class="text-3xl font-bold tracking-tight">{{ team.name }}</h1>
                            <Badge variant="outline" class="font-mono text-xs uppercase tracking-wider">
                                /{{ team.slug }}
                            </Badge>
                        </div>
                        <p class="text-muted-foreground max-w-xl line-clamp-2">
                            {{ team.description || 'No description provided for this team.' }}
                        </p>
                        <div class="flex items-center gap-4 mt-3 text-sm text-muted-foreground">
                            <div class="flex items-center gap-1.5">
                                <Calendar class="h-4 w-4" />
                                Created on {{ team.created_at }}
                            </div>
                            <div class="flex items-center gap-1.5">
                                <Users class="h-4 w-4" />
                                {{ team.members_count }} members
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link v-if="team.can_manage_members" :href="`/teams/${team.id}/edit`">
                        <Button variant="outline">
                            <Edit2 class="h-4 w-4 mr-2" />
                            Edit Team
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Team Info & Stats -->
                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Team Owner</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center border border-blue-200">
                                    <Shield class="h-5 w-5 text-blue-600" />
                                </div>
                                <div>
                                    <p class="font-semibold text-sm">{{ team.owner.name }}</p>
                                    <p class="text-xs text-muted-foreground text-wrap break-all">{{ team.owner.email }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Quick Links</CardTitle>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div class="flex flex-col">
                                <Link href="#" class="flex items-center gap-3 p-3 hover:bg-slate-50 transition-colors text-sm">
                                    <Globe class="h-4 w-4 text-slate-400" />
                                    Team Portfolio
                                </Link>
                                <Separator />
                                <Link href="#" class="flex items-center gap-3 p-3 hover:bg-slate-50 transition-colors text-sm">
                                    <Mail class="h-4 w-4 text-slate-400" />
                                    Contact Team
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Members List -->
                <div class="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0">
                            <div>
                                <CardTitle>Team Members</CardTitle>
                                <CardDescription>
                                    List of all people currently in this team.
                                </CardDescription>
                            </div>
                            <Button v-if="team.can_manage_members" size="sm" @click="isAddMemberOpen = true">
                                <UserPlus class="h-4 w-4 mr-2" />
                                Add Member
                            </Button>
                        </CardHeader>
                        <CardContent>
                            <div class="divide-y">
                                <div v-for="member in team.members" :key="member.id" class="flex flex-col sm:flex-row sm:items-center justify-between py-4 first:pt-0 last:pb-0 gap-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200 shrink-0 overflow-hidden">
                                            <img v-if="member.profile_photo_url" :src="member.profile_photo_url" class="h-full w-full object-cover" />
                                            <User v-else class="h-5 w-5 text-slate-400" />
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <p class="font-semibold text-sm">{{ member.name }}</p>
                                                <Badge v-if="member.id === team.owner.id" variant="default" class="text-[10px] h-4 px-1.5 uppercase tracking-tighter">Owner</Badge>
                                                <Badge v-else variant="outline" class="text-[10px] h-4 px-1.5 capitalize font-medium">
                                                    {{ member.pivot?.role || 'Member' }}
                                                </Badge>
                                            </div>
                                            <p class="text-xs text-muted-foreground">{{ member.email }}</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Member Actions -->
                                    <div v-if="team.can_manage_members && member.id !== team.owner.id" class="flex items-center gap-2">
                                        <Select 
                                            :model-value="member.pivot?.role || 'member'" 
                                            @update:model-value="(role: any) => updateRole(member, role)"
                                        >
                                            <SelectTrigger class="h-8 w-[100px] text-xs">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="member">Member</SelectItem>
                                                <SelectItem value="admin">Admin</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        
                                        <Button 
                                            variant="ghost" 
                                            size="icon" 
                                            class="h-8 w-8 text-red-500 hover:text-red-600 hover:bg-red-50"
                                            @click="removeMember(member)"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Add Member Dialog -->
        <Dialog v-model:open="isAddMemberOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add Team Member</DialogTitle>
                    <DialogDescription>
                        Add a new member to your team by their registered email address.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="addMember" class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="email">User Email</Label>
                        <Input 
                            id="email" 
                            v-model="addMemberForm.email" 
                            type="email" 
                            placeholder="user@example.com"
                            required
                        />
                        <InputError :message="addMemberForm.errors.email" />
                    </div>
                    <div class="space-y-2">
                        <Label for="role">Role</Label>
                        <Select v-model="addMemberForm.role">
                            <SelectTrigger id="role">
                                <SelectValue placeholder="Select a role" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="member">Member</SelectItem>
                                <SelectItem value="admin">Admin</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="addMemberForm.errors.role" />
                    </div>
                </form>
                <DialogFooter>
                    <Button variant="outline" @click="isAddMemberOpen = false">Cancel</Button>
                    <Button :disabled="addMemberForm.processing" @click="addMember">
                        {{ addMemberForm.processing ? 'Adding...' : 'Add Member' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
