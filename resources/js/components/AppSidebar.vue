<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Users, Package, FileText, Receipt, Clock } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Offline Time',
            href: '/offline-time',
            icon: Clock,
        },
        {
            title: 'Projects',
            href: '/projects',
            icon: Folder,
        },
    ];

    if (user.value?.role === 'admin') {
        items.push({
            title: 'Teams',
            href: '/teams',
            icon: Users,
        });
        items.push({
            title: 'Users',
            href: '/users',
            icon: Users,
        });
    }

    return items;
});

const team = computed(() => page.props.auth.team as any);

// const footerNavItems: NavItem[] = [
//     {
//         title: 'Github Repo',
//         href: 'https://github.com/laravel/vue-starter-kit',
//         icon: Folder,
//     },
//     {
//         title: 'Documentation',
//         href: 'https://laravel.com/docs/starter-kits#vue',
//         icon: BookOpen,
//     },
// ];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">

                            <template v-if="team">
                              
                                <template v-if="team?.data?.logo">
                                    <img :src="team.data.logo" :alt="team.data.name" class="h-8 w-8 rounded-lg object-cover" />
                                </template>
                                <template v-else>
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                        <Users class="h-4 w-4" />
                                    </div>
                                </template>
                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ team.data.name }}</span>
                                </div>
                            </template>
                            <template v-else>
                                <AppLogo />
                            </template>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <!-- <NavFooter :items="footerNavItems" /> -->
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
