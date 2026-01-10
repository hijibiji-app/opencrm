<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
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
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import { Plus, Users, Edit2, Trash2, Shield, Calendar, Search, RotateCcw, Eye } from 'lucide-vue-next';

interface Team {
	id: number;
	name: string;
	slug: string;
	logo: string | null;
	description: string | null;
	owner: {
		id: number;
		name: string;
	};
	members_count: number;
	created_at: string;
}

interface PaginatedTeams {
	data: Team[];
	links: any[];
	meta: {
		current_page: number;
		last_page: number;
		per_page: number;
		total: number;
	};
}

const props = defineProps<{
	teams: PaginatedTeams;
}>();

const deleteDialogOpen = ref(false);
const teamToDelete = ref<Team | null>(null);

const confirmDelete = (team: Team) => {
	teamToDelete.value = team;
	deleteDialogOpen.value = true;
};

const deleteTeam = () => {
	if (teamToDelete.value) {
		router.delete(`/teams/${teamToDelete.value.id}`, {
			onSuccess: () => {
				deleteDialogOpen.value = false;
				teamToDelete.value = null;
			},
		});
	}
};

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
	{
		title: 'Teams',
		href: '/teams',
	}
];
</script>

<template>
	<Head title="Teams" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="container mx-auto max-w-7xl p-4">
			<!-- Header -->
			<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
				<div>
					<h1 class="text-2xl font-bold tracking-tight">Teams</h1>
					<p class="text-sm text-muted-foreground">
                        Manage your teams and collaboration groups.
					</p>
				</div>
				<div class="flex gap-2 mt-4 md:mt-0">
					<Link href="/teams/create">
						<Button>
							<Plus class="h-4 w-4 mr-2" />
							Create Team
						</Button>
					</Link>
				</div>
			</div>

			<!-- Success Alert -->
			<Alert v-if="($page.props.flash as any)?.success" class="mb-6 border-green-500 bg-green-50">
				<AlertDescription class="text-green-800">
					{{ ($page.props.flash as any).success }}
				</AlertDescription>
			</Alert>

			<!-- Teams Table -->
			<Card class="overflow-hidden">
				<CardContent class="p-0">
					<div class="overflow-x-auto">
						<Table>
							<TableHeader class="bg-muted/50">
								<TableRow>
									<TableHead class="w-[300px]">Team</TableHead>
									<TableHead>Owner</TableHead>
									<TableHead>Members</TableHead>
									<TableHead>Created At</TableHead>
									<TableHead class="text-right">Actions</TableHead>
								</TableRow>
							</TableHeader>
							<TableBody>
								<TableRow v-if="teams.data.length === 0">
									<TableCell colspan="5" class="text-center py-12">
										<div class="flex flex-col items-center justify-center text-muted-foreground">
											<Users class="h-12 w-12 mb-4 opacity-20" />
											<p class="text-lg font-medium">No teams found</p>
											<p class="text-sm mt-2">Start by creating your first team.</p>
										</div>
									</TableCell>
								</TableRow>
								<TableRow v-for="team in teams.data" :key="team.id" class="hover:bg-muted/50 transition-colors">
									<TableCell>
										<div class="flex items-center gap-3">
											<div class="h-10 w-10 rounded-lg bg-slate-100 flex items-center justify-center overflow-hidden border">
												<img v-if="team.logo" :src="team.logo" class="h-full w-full object-cover" />
												<Users v-else class="h-5 w-5 text-slate-400" />
											</div>
											<Link :href="'/teams/' + team.id" class="group/title">
												<p class="font-semibold text-sm group-hover/title:text-primary transition-colors">{{ team.name }}</p>
												<p class="text-xs text-muted-foreground group-hover/title:text-primary/70 transition-colors">/{{ team.slug }}</p>
											</Link>
										</div>
									</TableCell>
									<TableCell>
										<div class="flex items-center gap-2">
											<Shield class="h-3.5 w-3.5 text-blue-500" />
											<span class="text-sm">{{ team.owner.name }}</span>
										</div>
									</TableCell>
									<TableCell>
										<Badge variant="secondary" class="font-normal">
											{{ team.members_count }} member{{ team.members_count !== 1 ? 's' : '' }}
										</Badge>
									</TableCell>
									<TableCell class="text-sm text-muted-foreground">
										<div class="flex items-center gap-1.5">
											<Calendar class="h-3.5 w-3.5" />
											{{ team.created_at }}
										</div>
									</TableCell>
									<TableCell class="text-right">
										<div class="flex items-center justify-end gap-2">
											<Link :href="'/teams/' + team.id">
												<Button variant="ghost" size="icon" class="h-8 w-8" title="View & Manage Members">
													<Eye class="h-3.5 w-3.5" />
												</Button>
											</Link>
											<Link :href="'/teams/' + team.id + '/edit'">
												<Button variant="ghost" size="icon" class="h-8 w-8">
													<Edit2 class="h-3.5 w-3.5" />
												</Button>
											</Link>
											<Button variant="ghost" size="icon" class="h-8 w-8 text-red-500 hover:text-red-600 hover:bg-red-50" @click="confirmDelete(team)">
												<Trash2 class="h-3.5 w-3.5" />
											</Button>
										</div>
									</TableCell>
								</TableRow>
							</TableBody>
						</Table>
					</div>

					<!-- Pagination -->
					<div v-if="teams.meta.last_page > 1" class="flex items-center justify-between px-6 py-4 border-t">
						<div class="text-sm text-muted-foreground">
							Showing {{ teams.meta.total > 0 ? (teams.meta.current_page - 1) * teams.meta.per_page + 1 : 0 }} to
							{{ Math.min(teams.meta.current_page * teams.meta.per_page, teams.meta.total) }} of
							{{ teams.meta.total }} teams
						</div>
						<div class="flex gap-2">
							<Link
								v-for="link in teams.links"
								:key="link.label"
								:href="link.url || '#'"
								:class="[
									'px-3 py-1 text-sm rounded border transition-colors',
									link.active
										? 'bg-primary text-primary-foreground border-primary'
										: 'hover:bg-muted border-transparent',
									!link.url && 'opacity-50 cursor-not-allowed pointer-events-none',
								]"
								v-html="link.label"
							/>
						</div>
					</div>
				</CardContent>
			</Card>
		</div>

		<!-- Delete Confirmation Dialog -->
		<Dialog v-model:open="deleteDialogOpen">
			<DialogContent>
				<DialogHeader>
					<DialogTitle>Delete Team</DialogTitle>
					<DialogDescription>
						Are you sure you want to delete the team <span class="font-bold">"{{ teamToDelete?.name }}"</span>? This action cannot be undone.
					</DialogDescription>
				</DialogHeader>
				<DialogFooter>
					<Button variant="outline" @click="deleteDialogOpen = false">Cancel</Button>
					<Button variant="destructive" @click="deleteTeam">Delete Team</Button>
				</DialogFooter>
			</DialogContent>
		</Dialog>
	</AppLayout>
</template>
