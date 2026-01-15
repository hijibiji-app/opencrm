<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import {
    AlertCircle,
    Calendar,
    CheckCircle2,
    TrendingUp,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    pace: {
        monthly_target_minutes: number;
        monthly_target_formatted: string;
        total_worked_minutes: number;
        total_worked_formatted: string;
        remaining_minutes: number;
        remaining_formatted: string;
        remaining_working_days: number;
        total_working_days: number;
        required_daily_minutes: number;
        required_daily_formatted: string;
        status: 'on_track' | 'behind' | 'missed' | 'completed';
        ssm_configured: boolean;
    };
}>();

const progressPercent = computed(() => {
    if (props.pace.monthly_target_minutes === 0) return 0;
    return Math.min(
        100,
        (props.pace.total_worked_minutes / props.pace.monthly_target_minutes) *
            100,
    );
});

const isCompleted = computed(() => props.pace.status === 'completed');

// Safely format remaining days (positive integer)
const formattedDaysLeft = computed(() => {
    return Math.max(0, Math.ceil(props.pace.remaining_working_days));
});
</script>

<template>
    <Card>
        <CardHeader class="pb-2">
            <div class="flex items-center justify-between">
                <CardTitle class="flex items-center gap-2 text-base">
                    <Calendar class="h-5 w-5 text-primary" />
                    Monthly Pacing
                </CardTitle>
            </div>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- Progress Bar -->
            <div class="space-y-2">
                <Progress :model-value="progressPercent" class="h-3" />
                <div class="flex justify-between text-xs text-muted-foreground">
                    <span>{{ pace.total_worked_formatted }} worked</span>
                    <span>{{ pace.monthly_target_formatted }} goal</span>
                </div>
            </div>

            <!-- Main Status & Required Pace -->
            <div
                v-if="isCompleted"
                class="rounded-lg bg-green-50 p-4 text-center dark:bg-green-900/20"
            >
                <div class="flex flex-col items-center justify-center gap-2">
                    <CheckCircle2
                        class="h-8 w-8 text-green-600 dark:text-green-400"
                    />
                    <h3
                        class="text-lg font-bold text-green-700 dark:text-green-300"
                    >
                        Monthly Target Reached!
                    </h3>
                    <p class="text-sm text-green-600/80 dark:text-green-400/80">
                        Great job! You've hit your
                        {{ pace.monthly_target_formatted }} goal.
                    </p>
                </div>
            </div>

            <div v-else class="rounded-lg border bg-card p-4 shadow-sm">
                <div
                    class="flex flex-col items-center justify-center gap-1 text-center"
                >
                    <p
                        class="flex items-center gap-2 text-sm font-medium text-muted-foreground"
                    >
                        <TrendingUp class="h-4 w-4" />
                        Required Daily Pace
                    </p>
                    <div class="text-3xl font-bold tracking-tight text-primary">
                        {{ pace.required_daily_formatted }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        per day for {{ formattedDaysLeft }} working days
                    </p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 gap-3 text-center sm:grid-cols-4">
                <div class="rounded-lg border bg-muted/50 p-2">
                    <p class="text-[10px] text-muted-foreground uppercase">
                        Month Goal
                    </p>
                    <p class="font-semibold">
                        {{ pace.monthly_target_formatted }}
                    </p>
                </div>
                <div class="rounded-lg border bg-muted/50 p-2">
                    <p class="text-[10px] text-muted-foreground uppercase">
                        Completed
                    </p>
                    <p class="font-semibold">
                        {{ pace.total_worked_formatted }}
                    </p>
                </div>
                <div class="rounded-lg border bg-muted/50 p-2">
                    <p class="text-[10px] text-muted-foreground uppercase">
                        Remaining
                    </p>
                    <div>
                        <div class="leading-tight font-semibold">
                            {{ pace.remaining_formatted }}
                        </div>
                        <div class="text-[10px] text-muted-foreground">
                            {{ formattedDaysLeft }} days left
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border bg-muted/50 p-2">
                    <p class="text-[10px] text-muted-foreground uppercase">
                        Status
                    </p>
                    <p
                        class="font-semibold capitalize"
                        :class="{
                            'text-green-600': pace.status === 'on_track',
                            'text-orange-500': pace.status === 'behind',
                            'text-red-500': pace.status === 'missed',
                        }"
                    >
                        {{ pace.status.replace('_', ' ') }}
                    </p>
                </div>
            </div>

            <!-- Configuration Warning -->
            <div
                v-if="!pace.ssm_configured"
                class="flex items-center gap-2 rounded-lg bg-blue-50 p-3 text-xs text-blue-800 dark:bg-blue-900/20 dark:text-blue-200"
            >
                <AlertCircle class="h-4 w-4 flex-shrink-0" />
                <span>Online time not tracked. Configure SSM in Settings.</span>
            </div>
        </CardContent>
    </Card>
</template>
