<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    BarChart3,
    Calendar,
    Clock,
    TrendingUp,
    CheckCircle2,
    AlertCircle,
    Users,
    Activity,
    Briefcase,
    ChevronRight,
    ArrowLeftRight,
} from 'lucide-vue-next';

interface User {
    id: number;
    name: string;
}

interface MonthlyTrackerItem {
    month: string;
    month_name: string;
    offline_minutes: number;
    offline_formatted: string;
    online_minutes: number;
    online_formatted: string;
    total_minutes: number;
    total_formatted: string;
    target_minutes: number;
    target_formatted: string;
    working_days: number;
    status: 'on_track' | 'completed' | 'behind' | 'missed';
    progress_percent: number;
}

interface PeakDayDetails {
    date: string;
    minutes: number;
    formatted: string;
    offline_formatted: string;
    online_formatted: string;
}

interface WeeklyDistributionItem {
    day_index: number;
    day_name: string;
    total_minutes: number;
    total_formatted: string;
    average_minutes: number;
    average_formatted: string;
    active_days: number;
}

interface SelectedMonthDetails {
    month: string;
    month_name: string;
    total_offline_minutes: number;
    total_offline_formatted: string;
    total_online_minutes: number;
    total_online_formatted: string;
    total_minutes: number;
    total_formatted: string;
    daily_average_minutes: number;
    daily_average_formatted: string;
    working_days: number;
    days_worked: number;
    target_minutes: number;
    target_formatted: string;
    progress_percent: number;
    offline_ratio: number;
    online_ratio: number;
    peak_day: PeakDayDetails | null;
    weekly_distribution: WeeklyDistributionItem[];
    insights: string[];
}

interface PurposeBreakdownItem {
    purpose: string;
    minutes: number;
    formatted: string;
    percentage: number;
}

interface DailyChartItem {
    day: number;
    label: string;
    is_friday: boolean;
    offline_minutes: number;
    offline_hours: number;
    online_minutes: number;
    online_hours: number;
    total_minutes: number;
    total_hours: number;
}

interface Team {
    id: number;
    name: string;
}

interface OfflineTimeEntry {
    id: number;
    user_id: number;
    date: string;
    start_time: string;
    end_time: string;
    duration_minutes: number;
    purpose: string;
    description?: string;
    team?: Team;
}

const props = defineProps<{
    monthlyTracker: MonthlyTrackerItem[];
    selectedMonth: SelectedMonthDetails;
    purposeBreakdown: PurposeBreakdownItem[];
    dailyChartData: DailyChartItem[];
    entries: OfflineTimeEntry[];
    manageableUsers: User[];
    targetUserId: number;
    ssmConfigured: boolean;
}>();

// Navigation filters
const selectedUser = ref(props.targetUserId.toString());
const selectedMonthVal = ref(props.selectedMonth.month);

const currentYear = new Date().getFullYear();
const years = computed(() => {
    const list = [];
    for (let y = 2024; y <= currentYear + 1; y++) {
        list.push(y.toString());
    }
    return list;
});

const monthsList = [
    { value: '01', name: 'January' },
    { value: '02', name: 'February' },
    { value: '03', name: 'March' },
    { value: '04', name: 'April' },
    { value: '05', name: 'May' },
    { value: '06', name: 'June' },
    { value: '07', name: 'July' },
    { value: '08', name: 'August' },
    { value: '09', name: 'September' },
    { value: '10', name: 'October' },
    { value: '11', name: 'November' },
    { value: '12', name: 'December' },
];

const selectedYear = ref(props.selectedMonth.month.split('-')[0]);
const selectedMonthPart = ref(props.selectedMonth.month.split('-')[1]);

// Sync selectedYear and selectedMonthPart when selectedMonthVal changes (e.g. from clicking tracker card)
watch(selectedMonthVal, (newVal) => {
    const parts = newVal.split('-');
    if (parts.length === 2) {
        selectedYear.value = parts[0];
        selectedMonthPart.value = parts[1];
    }
});

// Sync selectedMonthVal when dropdowns are changed
watch([selectedYear, selectedMonthPart], ([newYear, newMonth]) => {
    selectedMonthVal.value = `${newYear}-${newMonth}`;
});

const handleFiltersChange = () => {
    router.get('/analytics', {
        user_id: selectedUser.value,
        month: selectedMonthVal.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch([selectedUser, selectedMonthVal], () => {
    handleFiltersChange();
});

const selectMonth = (monthStr: string) => {
    selectedMonthVal.value = monthStr;
};

// Formatting helpers
const formatDuration = (minutes: number): string => {
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (h > 0 && m > 0) return `${h}h ${m}m`;
    if (h > 0) return `${h}h`;
    return `${m}m`;
};

const formatInsight = (text: string) => {
    return text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
};

const formatTime = (timeStr: string) => {
    if (!timeStr) return '';
    const parts = timeStr.split(':');
    if (parts.length < 2) return timeStr;
    const h = parseInt(parts[0]);
    const m = parts[1];
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h % 12 || 12;
    return `${h12}:${m} ${ampm}`;
};

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

// Calculate max hours for chart scale
const maxDailyHours = computed(() => {
    const hours = props.dailyChartData.map(d => d.total_hours);
    return Math.max(...hours, 8); // scale to at least 8 hours visually
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Analytics', href: '/analytics' },
];

const getStatusColor = (status: string) => {
    switch (status) {
        case 'completed': return 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20';
        case 'on_track': return 'bg-blue-500/10 text-blue-500 border-blue-500/20';
        case 'behind': return 'bg-amber-500/10 text-amber-500 border-amber-500/20';
        case 'missed': return 'bg-rose-500/10 text-rose-500 border-rose-500/20';
        default: return 'bg-muted text-muted-foreground';
    }
};
</script>

<template>
    <Head title="Analytics" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto space-y-8 p-4 md:p-6 max-w-7xl">
            
            <!-- Top Header & Filter Controls -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b pb-6">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Analytics</h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Track your monthly trends, target achievements, and offline vs online balance.
                    </p>
                </div>
                
                <div class="flex flex-wrap items-center gap-3">
                    <!-- User Selector (Admin/Manager Only) -->
                    <div v-if="manageableUsers && manageableUsers.length > 0" class="flex items-center gap-2">
                        <Users class="h-4 w-4 text-muted-foreground" />
                        <Select v-model="selectedUser">
                            <SelectTrigger class="w-[180px]">
                                <SelectValue placeholder="Select User" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="u in manageableUsers" :key="u.id" :value="u.id.toString()">
                                    {{ u.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Month & Year Selectors -->
                    <div class="flex items-center gap-2">
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                        <Select v-model="selectedMonthPart">
                            <SelectTrigger class="w-[130px]">
                                <SelectValue placeholder="Month" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="m in monthsList" :key="m.value" :value="m.value">
                                    {{ m.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        
                        <Select v-model="selectedYear">
                            <SelectTrigger class="w-[95px]">
                                <SelectValue placeholder="Year" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="y in years" :key="y" :value="y">
                                    {{ y }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </div>

            <!-- Smart Insights Banner -->
            <div v-if="selectedMonth.insights && selectedMonth.insights.length > 0" class="rounded-xl border bg-gradient-to-r from-blue-500/10 via-indigo-500/5 to-purple-500/10 p-5 shadow-sm">
                <div class="flex items-center gap-2 mb-3">
                    <div class="h-8 w-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <Activity class="h-4 w-4" />
                    </div>
                    <div>
                        <h2 class="text-base font-bold tracking-tight">Smart Analysis Insights</h2>
                        <p class="text-xs text-muted-foreground">Automated observations of your logged hours and target pacing</p>
                    </div>
                </div>
                
                <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3 mt-4">
                    <div 
                        v-for="(insight, index) in selectedMonth.insights" 
                        :key="index" 
                        class="flex items-start gap-2.5 text-xs text-muted-foreground bg-background/50 backdrop-blur-sm border rounded-lg p-3"
                    >
                        <span class="flex-shrink-0 mt-0.5" v-html="insight.split(' ')[0]"></span>
                        <div class="leading-relaxed">
                            <span v-html="formatInsight(insight.substring(insight.indexOf(' ') + 1))"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI Cards Overview -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <!-- Card 1: Total Hours -->
                <Card class="relative overflow-hidden">
                    <CardHeader class="pb-2">
                        <CardDescription class="text-xs font-semibold uppercase tracking-wider">Total Tracked</CardDescription>
                        <CardTitle class="text-3xl font-bold text-primary mt-1">{{ selectedMonth.total_formatted }}</CardTitle>
                    </CardHeader>
                    <CardContent class="text-xs text-muted-foreground">
                        Offline + Online combined
                    </CardContent>
                    <div class="absolute bottom-0 right-0 p-3 opacity-10">
                        <Activity class="h-12 w-12 text-primary" />
                    </div>
                </Card>

                <!-- Card 2: Offline Time -->
                <Card class="relative overflow-hidden border-l-4 border-l-indigo-500">
                    <CardHeader class="pb-2">
                        <CardDescription class="text-xs font-semibold uppercase tracking-wider text-indigo-500">Offline Time</CardDescription>
                        <CardTitle class="text-3xl font-bold text-indigo-500 mt-1">{{ selectedMonth.total_offline_formatted }}</CardTitle>
                    </CardHeader>
                    <CardContent class="text-xs text-muted-foreground">
                        Manually logged activities
                    </CardContent>
                    <div class="absolute bottom-0 right-0 p-3 opacity-10">
                        <Clock class="h-12 w-12 text-indigo-500" />
                    </div>
                </Card>

                <!-- Card 3: Online Time (SSM) -->
                <Card class="relative overflow-hidden border-l-4 border-l-emerald-500">
                    <CardHeader class="pb-2">
                        <CardDescription class="text-xs font-semibold uppercase tracking-wider text-emerald-500">Online (SSM)</CardDescription>
                        <CardTitle class="text-3xl font-bold text-emerald-500 mt-1">{{ selectedMonth.total_online_formatted }}</CardTitle>
                    </CardHeader>
                    <CardContent class="text-xs text-muted-foreground flex items-center gap-1">
                        <span v-if="ssmConfigured" class="flex h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span v-else class="flex h-2 w-2 rounded-full bg-amber-500"></span>
                        {{ ssmConfigured ? 'ScreenshotMonitor active' : 'SSM Not Configured' }}
                    </CardContent>
                    <div class="absolute bottom-0 right-0 p-3 opacity-10">
                        <ArrowLeftRight class="h-12 w-12 text-emerald-500" />
                    </div>
                </Card>

                <!-- Card 4: Daily Average -->
                <Card class="relative overflow-hidden">
                    <CardHeader class="pb-2">
                        <CardDescription class="text-xs font-semibold uppercase tracking-wider">Daily Average</CardDescription>
                        <CardTitle class="text-3xl font-bold mt-1">{{ selectedMonth.daily_average_formatted }}</CardTitle>
                    </CardHeader>
                    <CardContent class="text-xs text-muted-foreground">
                        Across {{ selectedMonth.days_worked }} days with activity
                    </CardContent>
                    <div class="absolute bottom-0 right-0 p-3 opacity-10">
                        <TrendingUp class="h-12 w-12 text-muted-foreground" />
                    </div>
                </Card>

                <!-- Card 5: Month Target -->
                <Card class="relative overflow-hidden bg-primary/5 border border-primary/20">
                    <CardHeader class="pb-2">
                        <CardDescription class="text-xs font-semibold uppercase tracking-wider text-primary">Goal Achieved</CardDescription>
                        <CardTitle class="text-3xl font-bold text-primary mt-1">{{ selectedMonth.progress_percent }}%</CardTitle>
                    </CardHeader>
                    <CardContent class="text-xs text-muted-foreground">
                        Target: {{ selectedMonth.target_formatted }} ({{ selectedMonth.working_days }} working days)
                    </CardContent>
                </Card>
            </div>

            <!-- Past 6 Months Tracker -->
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <Calendar class="h-5 w-5 text-primary" />
                    <h2 class="text-lg font-semibold tracking-tight">Monthly Tracker</h2>
                </div>
                
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
                    <div 
                        v-for="item in monthlyTracker" 
                        :key="item.month" 
                        @click="selectMonth(item.month)"
                        class="cursor-pointer border rounded-xl p-4 transition-all duration-200 bg-card hover:shadow-md hover:border-primary/50 relative flex flex-col justify-between h-40"
                        :class="selectedMonthVal === item.month ? 'ring-2 ring-primary border-primary' : 'shadow-sm'"
                    >
                        <div>
                            <div class="flex items-start justify-between">
                                <span class="font-bold text-sm tracking-tight">{{ item.month_name }}</span>
                                <Badge 
                                    variant="outline" 
                                    class="text-[10px] px-1.5 py-0.5 font-medium capitalize"
                                    :class="getStatusColor(item.status)"
                                >
                                    {{ item.status.replace('_', ' ') }}
                                </Badge>
                            </div>
                            
                            <div class="mt-3 text-xs text-muted-foreground">
                                <div class="flex justify-between font-medium">
                                    <span>Tracked:</span>
                                    <span class="text-foreground font-semibold">{{ item.total_formatted }}</span>
                                </div>
                                <div class="flex justify-between text-[11px] mt-0.5 text-muted-foreground/80">
                                    <span>Target:</span>
                                    <span>{{ item.target_formatted }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-1.5 mt-auto">
                            <Progress :model-value="item.progress_percent" class="h-1.5 bg-muted" />
                            <div class="flex justify-between text-[10px] text-muted-foreground font-medium">
                                <span>{{ item.progress_percent }}% achieved</span>
                                <span>{{ item.working_days }} days</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Month Detailed Breakdown Section -->
            <div class="grid gap-6 lg:grid-cols-3">
                
                <!-- Daily Activity Chart (2 cols large screen) -->
                <Card class="lg:col-span-2 shadow-sm">
                    <CardHeader class="pb-2">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <CardTitle class="text-base">Daily Activity Trend</CardTitle>
                                <CardDescription>Hourly breakdown of logged time in {{ selectedMonth.month_name }}</CardDescription>
                            </div>
                            <!-- Legend -->
                            <div class="flex items-center gap-4 text-xs font-medium">
                                <div class="flex items-center gap-1.5">
                                    <span class="h-3 w-3 rounded-sm bg-indigo-500"></span>
                                    <span>Offline</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="h-3 w-3 rounded-sm bg-emerald-500"></span>
                                    <span>Online (SSM)</span>
                                </div>
                            </div>
                        </div>
                    </CardHeader>
                    
                    <CardContent class="pt-6">
                        <!-- Chart Grid Wrapper -->
                        <div class="relative w-full overflow-x-auto pb-4">
                            <div class="flex items-end justify-between min-w-[640px] px-2 h-64 border-b border-muted">
                                
                                <!-- Render each day's bar -->
                                <div 
                                    v-for="day in dailyChartData" 
                                    :key="day.day" 
                                    class="flex flex-col items-center flex-1 group relative px-0.5"
                                >
                                    <!-- Tooltip content -->
                                    <div class="absolute bottom-full mb-3 hidden group-hover:flex flex-col items-center pointer-events-none z-30 transition-all duration-200">
                                        <div class="bg-popover text-popover-foreground text-xs rounded-lg border p-3 shadow-lg min-w-[140px] space-y-1">
                                            <p class="font-bold text-foreground">{{ day.label }}</p>
                                            <div class="flex justify-between gap-4 text-[11px]">
                                                <span class="text-indigo-400">Offline:</span>
                                                <span class="font-semibold">{{ formatDuration(day.offline_minutes) }}</span>
                                            </div>
                                            <div class="flex justify-between gap-4 text-[11px]">
                                                <span class="text-emerald-400">Online:</span>
                                                <span class="font-semibold">{{ formatDuration(day.online_minutes) }}</span>
                                            </div>
                                            <hr class="my-1 border-muted" />
                                            <div class="flex justify-between gap-4 text-xs font-bold text-foreground">
                                                <span>Total:</span>
                                                <span>{{ formatDuration(day.total_minutes) }}</span>
                                            </div>
                                        </div>
                                        <div class="w-3 h-3 bg-popover border-r border-b rotate-45 -mt-1.5 shadow-md"></div>
                                    </div>
                                    
                                    <!-- Stacked Bar representation -->
                                    <div 
                                        class="w-full bg-muted rounded-t-sm overflow-hidden flex flex-col justify-end transition-all duration-200 group-hover:opacity-90"
                                        :class="[
                                            day.is_friday ? 'border-x border-dashed border-muted-foreground/35 bg-muted/20' : '',
                                            day.total_minutes > 0 ? 'bg-muted/40' : 'h-1.5'
                                        ]"
                                        :style="day.total_minutes > 0 ? { height: `${(day.total_hours / maxDailyHours) * 220}px` } : {}"
                                    >
                                        <!-- Online Stack (Emerald) -->
                                        <div 
                                            v-if="day.online_minutes > 0"
                                            class="bg-emerald-500 w-full"
                                            :style="{ height: `${(day.online_minutes / day.total_minutes) * 100}%` }"
                                        ></div>
                                        
                                        <!-- Offline Stack (Indigo) -->
                                        <div 
                                            v-if="day.offline_minutes > 0"
                                            class="bg-indigo-500 w-full"
                                            :style="{ height: `${(day.offline_minutes / day.total_minutes) * 100}%` }"
                                        ></div>
                                    </div>

                                    <!-- Day label -->
                                    <span 
                                        class="text-[10px] mt-2 font-medium"
                                        :class="day.is_friday ? 'text-rose-500 font-bold' : 'text-muted-foreground'"
                                    >
                                        {{ day.day }}
                                    </span>
                                </div>

                            </div>
                        </div>
                        
                        <div class="mt-4 flex justify-between text-xs text-muted-foreground">
                            <span>Day 1</span>
                            <span>Weekend/Fridays marked in red</span>
                            <span>Day {{ dailyChartData.length }}</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Purpose Breakdown Card -->
                <Card class="shadow-sm">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base flex items-center gap-2">
                            <Briefcase class="h-4 w-4 text-primary" />
                            Offline Purpose Breakdown
                        </CardTitle>
                        <CardDescription>Distribution of manual logs by purpose</CardDescription>
                    </CardHeader>
                    
                    <CardContent class="pt-4 space-y-5">
                        <div v-if="purposeBreakdown && purposeBreakdown.length > 0" class="space-y-4">
                            <div v-for="item in purposeBreakdown" :key="item.purpose" class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs font-semibold">
                                    <span class="flex items-center gap-1.5">
                                        <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                                        {{ item.purpose }}
                                    </span>
                                    <span class="text-muted-foreground">{{ item.formatted }} ({{ item.percentage }}%)</span>
                                </div>
                                <Progress :model-value="item.percentage" class="h-2 bg-muted indigo-progress" />
                            </div>
                        </div>
                        
                        <div v-else class="h-48 flex flex-col items-center justify-center text-center p-4">
                            <AlertCircle class="h-8 w-8 text-muted-foreground opacity-55 mb-2" />
                            <p class="text-sm font-medium text-muted-foreground">No offline data logged for this month</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Weekday Productivity Rhythm Card (2 cols) -->
                <Card class="lg:col-span-2 shadow-sm">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base flex items-center gap-2">
                            <TrendingUp class="h-4 w-4 text-primary" />
                            Weekday Productivity Rhythm
                        </CardTitle>
                        <CardDescription>Average tracked duration per active weekday in {{ selectedMonth.month_name }}</CardDescription>
                    </CardHeader>
                    <CardContent class="pt-4">
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-7">
                            <div 
                                v-for="day in selectedMonth.weekly_distribution" 
                                :key="day.day_name" 
                                class="rounded-xl border bg-card p-3 flex flex-col justify-between h-32 relative shadow-sm"
                                :class="day.day_name === 'Friday' ? 'border-rose-500/20 bg-rose-500/[0.02]' : ''"
                            >
                                <span class="text-xs font-semibold text-muted-foreground">{{ day.day_name.substring(0, 3) }}</span>
                                
                                <div class="my-2">
                                    <div class="text-base font-bold text-primary">{{ day.average_formatted }}</div>
                                    <span class="text-[9px] text-muted-foreground leading-none">avg/active</span>
                                </div>
                                
                                <div class="text-[9px] text-muted-foreground border-t pt-1.5 mt-auto">
                                    Total: {{ day.total_formatted }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Work Balance & Peak Activity Card (1 col) -->
                <Card class="shadow-sm">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base flex items-center gap-2">
                            <ArrowLeftRight class="h-4 w-4 text-primary" />
                            Work Balance & Peak Activity
                        </CardTitle>
                        <CardDescription>Online vs Offline ratio and highest activity metrics</CardDescription>
                    </CardHeader>
                    
                    <CardContent class="pt-4 space-y-6">
                        <!-- Ratio Balance Progress -->
                        <div class="space-y-2">
                            <div class="flex justify-between text-xs font-semibold">
                                <span class="text-indigo-500">Offline ({{ selectedMonth.offline_ratio }}%)</span>
                                <span class="text-emerald-500">Online ({{ selectedMonth.online_ratio }}%)</span>
                            </div>
                            <div class="h-3 w-full rounded-full bg-muted overflow-hidden flex">
                                <div class="bg-indigo-500 h-full" :style="{ width: `${selectedMonth.offline_ratio}%` }"></div>
                                <div class="bg-emerald-500 h-full" :style="{ width: `${selectedMonth.online_ratio}%` }"></div>
                            </div>
                            <p class="text-[10px] text-muted-foreground leading-normal">
                                Split of manual offline logging versus automatic ScreenshotMonitor online sync.
                              </p>
                        </div>
                        
                        <hr class="border-muted" />
                        
                        <!-- Peak Day Metrics -->
                        <div v-if="selectedMonth.peak_day" class="space-y-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Highest Active Day</h4>
                            <div class="rounded-xl border bg-muted/20 p-3 space-y-2">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-muted-foreground font-medium">Date</span>
                                    <span class="font-bold text-foreground">{{ selectedMonth.peak_day.date }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-muted-foreground font-medium">Total Logged</span>
                                    <span class="font-extrabold text-primary">{{ selectedMonth.peak_day.formatted }}</span>
                                </div>
                                <div class="flex justify-between items-center text-[10px]">
                                    <span class="text-muted-foreground">Offline Logs</span>
                                    <span class="font-semibold text-indigo-500">{{ selectedMonth.peak_day.offline_formatted }}</span>
                                </div>
                                <div class="flex justify-between items-center text-[10px]">
                                    <span class="text-muted-foreground">Online Time</span>
                                    <span class="font-semibold text-emerald-500">{{ selectedMonth.peak_day.online_formatted }}</span>
                                </div>
                              </div>
                          </div>
                          
                          <div v-else class="h-28 flex flex-col items-center justify-center text-center text-muted-foreground">
                              <AlertCircle class="h-6 w-6 opacity-45 mb-1" />
                              <p class="text-xs">No active logs to calculate peak day</p>
                          </div>
                      </CardContent>
                  </Card>

            </div>

            <!-- Offline Time Logs List -->
            <Card class="shadow-sm">
                <CardHeader class="pb-2 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <CardTitle class="text-base">Offline Log details</CardTitle>
                        <CardDescription>Detailed manual entries logged for {{ selectedMonth.month_name }}</CardDescription>
                    </div>
                </CardHeader>
                
                <CardContent class="pt-0">
                    <div v-if="entries && entries.length > 0" class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Time Range</TableHead>
                                    <TableHead>Duration</TableHead>
                                    <TableHead>Purpose</TableHead>
                                    <TableHead>Team</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="entry in entries" :key="entry.id" class="group">
                                    <TableCell class="font-medium">{{ formatDate(entry.date) }}</TableCell>
                                    <TableCell class="text-muted-foreground text-xs font-medium">
                                        {{ formatTime(entry.start_time) }} - {{ formatTime(entry.end_time) }}
                                    </TableCell>
                                    <TableCell class="font-semibold">{{ formatDuration(entry.duration_minutes) }}</TableCell>
                                    <TableCell>
                                        <Badge variant="outline" class="font-medium text-[11px] py-0.5">
                                            {{ entry.purpose }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-muted-foreground text-xs">
                                        {{ entry.team ? entry.team.name : 'Personal' }}
                                    </TableCell>
                                    <TableCell class="max-w-xs truncate text-muted-foreground text-xs" :title="entry.description">
                                        {{ entry.description || 'No description provided' }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Link :href="`/offline-time/${entry.id}/edit`" class="inline-flex">
                                            <Button variant="ghost" size="sm" class="h-8 w-8 p-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <ChevronRight class="h-4 w-4" />
                                                <span class="sr-only">Edit</span>
                                            </Button>
                                        </Link>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    
                    <div v-else class="h-40 flex flex-col items-center justify-center text-center p-6 border border-dashed rounded-lg">
                        <Clock class="h-8 w-8 text-muted-foreground opacity-55 mb-2" />
                        <p class="text-sm font-semibold text-muted-foreground">No offline entries logged for this month</p>
                        <Link href="/offline-time/create" class="mt-2">
                            <Button size="sm">
                                <Plus class="h-4 w-4 mr-1.5" />
                                Log Offline Time
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>

<style scoped>
/* Customize Indigo progress color if needed to match theme */
:deep(.indigo-progress > div) {
    background-color: var(--color-indigo-500, #6366f1);
}
</style>
