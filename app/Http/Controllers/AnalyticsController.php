<?php

namespace App\Http\Controllers;

use App\Models\OfflineTimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    /**
     * Display the analytics page.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::user();

        // 1. Resolve users this user can manage (Owner/Admin of teams)
        $manageableTeamIds = \App\Models\Team::where('owner_id', $user->id)
            ->orWhereHas('members', function($q) use ($user) {
                $q->where('team_user.user_id', $user->id)->where('team_user.role', 'admin');
            })->pluck('id')->toArray();

        $manageableUsers = collect();
        if ($user->isAdmin()) {
            $manageableUsers = User::orderBy('name')->get(['id', 'name']);
        } elseif (count($manageableTeamIds) > 0) {
            $manageableUsers = User::whereHas('teams', function($q) use ($manageableTeamIds) {
                $q->whereIn('teams.id', $manageableTeamIds);
            })->orWhere('id', $user->id)->distinct()->orderBy('name')->get(['id', 'name']);
        }

        // 2. Resolve target user
        $targetUserId = $user->id;
        if ($request->has('user_id')) {
            $reqUserId = (int) $request->user_id;
            if ($user->isAdmin() || $manageableUsers->contains('id', $reqUserId)) {
                $targetUserId = $reqUserId;
            }
        }
        $targetUser = User::find($targetUserId) ?? $user;

        // 3. Resolve selected month (default: current month)
        $monthParam = $request->input('month', Carbon::now()->format('Y-m'));
        try {
            $selectedMonth = Carbon::parse($monthParam)->startOfMonth();
        } catch (\Exception $e) {
            $selectedMonth = Carbon::now()->startOfMonth();
        }

        // 4. Generate past 6 months history (Monthly Tracker)
        $monthlyTracker = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = Carbon::now()->subMonths($i)->startOfMonth();
            $monthStr = $monthDate->format('Y-m');

            // Offline Time
            $offlineMin = OfflineTimeEntry::where('user_id', $targetUser->id)
                ->whereYear('date', $monthDate->year)
                ->whereMonth('date', $monthDate->month)
                ->sum('duration_minutes');

            // Online Time from SSM
            $ssmData = $this->getSSMReportDataForMonth($targetUser, $monthDate);
            $onlineMin = $ssmData['total_minutes'];

            // Monthly Target calculation (8h per working day excluding Fridays)
            $totalDaysInMonth = $monthDate->daysInMonth;
            $fridaysInMonth = $this->countFridaysInMonth($monthDate);
            $workingDays = $totalDaysInMonth - $fridaysInMonth;
            $targetMin = $workingDays * 8 * 60;

            $totalWorkedMin = $offlineMin + $onlineMin;
            $remainingMin = max(0, $targetMin - $totalWorkedMin);

            // Determine status
            $status = 'on_track';
            if ($monthDate->isCurrentMonth()) {
                if ($totalWorkedMin >= $targetMin) {
                    $status = 'completed';
                } else {
                    // Check pacing
                    $now = Carbon::now();
                    $tomorrow = $now->copy()->addDay()->startOfDay();
                    $endOfMonth = $now->copy()->endOfMonth();
                    $remainingWorkingDays = 0;
                    if ($tomorrow->lte($endOfMonth)) {
                        $current = $tomorrow->copy();
                        while ($current->lte($endOfMonth)) {
                            if (!$current->isFriday()) {
                                $remainingWorkingDays++;
                            }
                            $current->addDay();
                        }
                    }
                    $requiredDailyMinutes = $remainingWorkingDays > 0 ? (int) ceil($remainingMin / $remainingWorkingDays) : 0;
                    
                    if ($remainingMin <= 0) {
                        $status = 'completed';
                    } elseif ($remainingWorkingDays === 0) {
                        $status = 'missed';
                    } elseif ($requiredDailyMinutes > 10 * 60) {
                        $status = 'behind';
                    } else {
                        $status = 'on_track';
                    }
                }
            } else {
                $status = $totalWorkedMin >= $targetMin ? 'completed' : 'missed';
            }

            $monthlyTracker[] = [
                'month' => $monthStr,
                'month_name' => $monthDate->format('F Y'),
                'offline_minutes' => (int) $offlineMin,
                'offline_formatted' => $this->formatDuration((int) $offlineMin),
                'online_minutes' => (int) $onlineMin,
                'online_formatted' => $this->formatDuration((int) $onlineMin),
                'total_minutes' => (int) $totalWorkedMin,
                'total_formatted' => $this->formatDuration((int) $totalWorkedMin),
                'target_minutes' => $targetMin,
                'target_formatted' => $this->formatDuration($targetMin),
                'working_days' => $workingDays,
                'status' => $status,
                'progress_percent' => $targetMin > 0 ? round(($totalWorkedMin / $targetMin) * 100, 1) : 0,
            ];
        }

        // 5. Gather detailed stats for the selected month
        $selectedOfflineMin = OfflineTimeEntry::where('user_id', $targetUser->id)
            ->whereYear('date', $selectedMonth->year)
            ->whereMonth('date', $selectedMonth->month)
            ->sum('duration_minutes');

        $selectedSSM = $this->getSSMReportDataForMonth($targetUser, $selectedMonth);
        $selectedOnlineMin = $selectedSSM['total_minutes'];
        $selectedTotalMin = $selectedOfflineMin + $selectedOnlineMin;

        // Daily average based on days where time was logged in that month
        $daysWithTime = OfflineTimeEntry::where('user_id', $targetUser->id)
            ->whereYear('date', $selectedMonth->year)
            ->whereMonth('date', $selectedMonth->month)
            ->distinct('date')
            ->count('date');

        // Also add unique days from SSM
        $ssmDays = count(array_filter($selectedSSM['daily_timeline'], fn($m) => $m > 0));
        $uniqueDaysWorked = OfflineTimeEntry::where('user_id', $targetUser->id)
            ->whereYear('date', $selectedMonth->year)
            ->whereMonth('date', $selectedMonth->month)
            ->pluck('date')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->merge(array_keys(array_filter($selectedSSM['daily_timeline'], fn($m) => $m > 0)))
            ->unique()
            ->count();

        $dailyAverageMin = $uniqueDaysWorked > 0 ? (int) ($selectedTotalMin / $uniqueDaysWorked) : 0;

        // Selected month details
        $daysInSelectedMonth = $selectedMonth->daysInMonth;
        $selectedFridays = $this->countFridaysInMonth($selectedMonth);
        $selectedWorkingDays = $daysInSelectedMonth - $selectedFridays;
        $selectedTargetMin = $selectedWorkingDays * 8 * 60;

        // Offline purpose breakdown
        $purposeData = OfflineTimeEntry::where('user_id', $targetUser->id)
            ->whereYear('date', $selectedMonth->year)
            ->whereMonth('date', $selectedMonth->month)
            ->select('purpose', \DB::raw('SUM(duration_minutes) as minutes'))
            ->groupBy('purpose')
            ->get()
            ->map(function ($row) use ($selectedOfflineMin) {
                return [
                    'purpose' => $row->purpose,
                    'minutes' => (int) $row->minutes,
                    'formatted' => $this->formatDuration((int) $row->minutes),
                    'percentage' => $selectedOfflineMin > 0 ? round(($row->minutes / $selectedOfflineMin) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('minutes')
            ->values();

        // Daily activity timeline data
        $dailyChartData = [];
        for ($day = 1; $day <= $daysInSelectedMonth; $day++) {
            $loopDate = Carbon::create($selectedMonth->year, $selectedMonth->month, $day)->startOfDay();
            $dateStr = $loopDate->format('Y-m-d');

            $dayOffline = (int) OfflineTimeEntry::where('user_id', $targetUser->id)
                ->whereDate('date', $loopDate)
                ->sum('duration_minutes');

            $dayOnline = (int) ($selectedSSM['daily_timeline'][$dateStr] ?? 0);
            $dayTotal = $dayOffline + $dayOnline;

            $dailyChartData[] = [
                'day' => $day,
                'label' => $loopDate->format('M d'),
                'is_friday' => $loopDate->isFriday(),
                'offline_minutes' => $dayOffline,
                'offline_hours' => round($dayOffline / 60, 2),
                'online_minutes' => $dayOnline,
                'online_hours' => round($dayOnline / 60, 2),
                'total_minutes' => $dayTotal,
                'total_hours' => round($dayTotal / 60, 2),
            ];
        }

        // List of entries for the selected month
        $entries = OfflineTimeEntry::where('user_id', $targetUser->id)
            ->whereYear('date', $selectedMonth->year)
            ->whereMonth('date', $selectedMonth->month)
            ->with(['team'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();

        // --- Advanced Analysis Calculations ---

        // 1. Online vs Offline Ratios
        $totalMinutes = $selectedOfflineMin + $selectedOnlineMin;
        $offlineRatio = $totalMinutes > 0 ? round(($selectedOfflineMin / $totalMinutes) * 100) : 0;
        $onlineRatio = $totalMinutes > 0 ? 100 - $offlineRatio : 0;

        // 2. Day-of-week Distribution & Averages
        $dayOfWeekTotals = array_fill(0, 7, 0); // index 0 = Sunday, 1 = Monday, ..., 6 = Saturday
        $dayOfWeekCounts = array_fill(0, 7, 0);
        
        foreach ($dailyChartData as $dayItem) {
            $loopDate = Carbon::create($selectedMonth->year, $selectedMonth->month, $dayItem['day'])->startOfDay();
            $dow = $loopDate->dayOfWeek; // 0 = Sunday, 1 = Monday, ..., 6 = Saturday
            $dayOfWeekTotals[$dow] += $dayItem['total_minutes'];
            
            // Count it as an active day if they worked > 0 minutes
            if ($dayItem['total_minutes'] > 0) {
                $dayOfWeekCounts[$dow]++;
            }
        }

        $dowNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $weeklyDistribution = [];
        for ($i = 0; $i < 7; $i++) {
            $avgMinutes = $dayOfWeekCounts[$i] > 0 ? (int)($dayOfWeekTotals[$i] / $dayOfWeekCounts[$i]) : 0;
            $weeklyDistribution[] = [
                'day_index' => $i,
                'day_name' => $dowNames[$i],
                'total_minutes' => $dayOfWeekTotals[$i],
                'total_formatted' => $this->formatDuration($dayOfWeekTotals[$i]),
                'average_minutes' => $avgMinutes,
                'average_formatted' => $this->formatDuration($avgMinutes),
                'active_days' => $dayOfWeekCounts[$i],
            ];
        }

        // 3. Peak Day Calculation
        $peakDay = null;
        $maxMinutes = 0;
        foreach ($dailyChartData as $dayItem) {
            if ($dayItem['total_minutes'] > $maxMinutes) {
                $maxMinutes = $dayItem['total_minutes'];
                $peakDay = $dayItem;
            }
        }
        
        $peakDayFormatted = null;
        if ($peakDay) {
            $peakDayDate = Carbon::create($selectedMonth->year, $selectedMonth->month, $peakDay['day'])->format('M d, Y');
            $peakDayFormatted = [
                'date' => $peakDayDate,
                'minutes' => $peakDay['total_minutes'],
                'formatted' => $this->formatDuration($peakDay['total_minutes']),
                'offline_formatted' => $this->formatDuration($peakDay['offline_minutes']),
                'online_formatted' => $this->formatDuration($peakDay['online_minutes']),
            ];
        }

        // 4. Generate Smart Insights List
        $insights = [];
        
        // Pacing Insight
        $pPercent = $selectedTargetMin > 0 ? round(($selectedTotalMin / $selectedTargetMin) * 100) : 0;
        if ($selectedMonth->isCurrentMonth()) {
            if ($pPercent >= 100) {
                $insights[] = "🎉 **Goal Reached:** You have exceeded your monthly goal of " . $this->formatDuration($selectedTargetMin) . "!";
            } else {
                $insights[] = "📈 **Goal Progress:** You have completed **{$pPercent}%** of your " . $this->formatDuration($selectedTargetMin) . " goal, with " . ($selectedWorkingDays - $uniqueDaysWorked) . " working days remaining.";
            }
        } else {
            if ($pPercent >= 100) {
                $insights[] = "🏆 **Target Achieved:** In {$selectedMonth->format('F Y')}, you met and exceeded your goal by logging " . $this->formatDuration($selectedTotalMin) . ".";
            } else {
                $insights[] = "⚠️ **Target Missed:** In {$selectedMonth->format('F Y')}, you logged **{$pPercent}%** of your " . $this->formatDuration($selectedTargetMin) . " goal.";
            }
        }

        // Peak Day Insight
        if ($peakDayFormatted) {
            $insights[] = "⚡ **Productivity Peak:** Your most active day was **{$peakDayFormatted['date']}**, logging **{$peakDayFormatted['formatted']}** (Offline: {$peakDayFormatted['offline_formatted']}, Online: {$peakDayFormatted['online_formatted']}).";
        }

        // Balance Insight
        if ($totalMinutes > 0) {
            $insights[] = "⚖️ **Time Balance:** Your time was split **{$offlineRatio}%** offline and **{$onlineRatio}%** online (SSM).";
        }

        // Most Productive Weekday Insight
        $bestWeekday = collect($weeklyDistribution)
            ->filter(fn($d) => $d['day_name'] !== 'Friday') // Ignore weekend if Friday
            ->sortByDesc('average_minutes')
            ->first();
            
        if ($bestWeekday && $bestWeekday['average_minutes'] > 0) {
            $insights[] = "🗓️ **Weekly Rhythm:** **{$bestWeekday['day_name']}** was your most active day of the week on average, with **{$bestWeekday['average_formatted']}** logged per day.";
        }

        // Most Logged Purpose Insight
        if ($purposeData && count($purposeData) > 0) {
            $topPurpose = $purposeData[0];
            $insights[] = "💼 **Primary Focus:** You spent **{$topPurpose['formatted']}** (**{$topPurpose['percentage']}%** of offline time) on **{$topPurpose['purpose']}** tasks.";
        }

        return Inertia::render('Analytics/Index', [
            'monthlyTracker' => $monthlyTracker,
            'selectedMonth' => [
                'month' => $monthParam,
                'month_name' => $selectedMonth->format('F Y'),
                'total_offline_minutes' => (int) $selectedOfflineMin,
                'total_offline_formatted' => $this->formatDuration((int) $selectedOfflineMin),
                'total_online_minutes' => (int) $selectedOnlineMin,
                'total_online_formatted' => $this->formatDuration((int) $selectedOnlineMin),
                'total_minutes' => (int) $selectedTotalMin,
                'total_formatted' => $this->formatDuration((int) $selectedTotalMin),
                'daily_average_minutes' => $dailyAverageMin,
                'daily_average_formatted' => $this->formatDuration($dailyAverageMin),
                'working_days' => $selectedWorkingDays,
                'days_worked' => $uniqueDaysWorked,
                'target_minutes' => $selectedTargetMin,
                'target_formatted' => $this->formatDuration($selectedTargetMin),
                'progress_percent' => $selectedTargetMin > 0 ? round(($selectedTotalMin / $selectedTargetMin) * 100, 1) : 0,
                // Advanced stats
                'offline_ratio' => $offlineRatio,
                'online_ratio' => $onlineRatio,
                'peak_day' => $peakDayFormatted,
                'weekly_distribution' => $weeklyDistribution,
                'insights' => $insights,
            ],
            'purposeBreakdown' => $purposeData,
            'dailyChartData' => $dailyChartData,
            'entries' => $entries,
            'manageableUsers' => $manageableUsers,
            'targetUserId' => $targetUserId,
            'ssmConfigured' => !empty($targetUser->ssm_api_token),
        ]);
    }

    /**
     * Get SSM report data (total and daily timeline) for a specific month.
     */
    private function getSSMReportDataForMonth(User $user, Carbon $monthDate): array
    {
        if (empty($user->ssm_api_token)) {
            return [
                'total_minutes' => 0,
                'daily_timeline' => [],
            ];
        }

        $monthStr = $monthDate->format('Y-m');
        $cacheKey = 'ssm_report_data_' . $user->id . '_' . $monthStr;

        return Cache::remember($cacheKey, 1800, function () use ($user, $monthDate) {
            try {
                $from = $monthDate->copy()->startOfMonth()->format('Y-m-d');
                // If it is current month, fetch up to today, otherwise end of month
                $to = $monthDate->isCurrentMonth()
                    ? Carbon::now()->format('Y-m-d')
                    : $monthDate->copy()->endOfMonth()->format('Y-m-d');

                // Get employmentId
                $commonResponse = Http::withoutVerifying()->withHeaders([
                    'X-SSM-Token' => $user->ssm_api_token,
                    'Accept' => 'application/json',
                ])->get('https://screenshotmonitor.com/api/v2/GetCommonData');

                if ($commonResponse->failed()) {
                    throw new \Exception('GetCommonData failed');
                }

                $employmentId = $commonResponse->json('employmentId');
                if (!$employmentId) {
                    $commonData = $commonResponse->json();
                    $employments = $commonData['employments'] ?? [];
                    if (!empty($employments)) {
                        $employmentId = $employments[0]['id'] ?? null;
                    }
                }

                if (!$employmentId) {
                    throw new \Exception('No employmentId found');
                }

                // Fetch monthly report
                $reportResponse = Http::withoutVerifying()->withHeaders([
                    'X-SSM-Token' => $user->ssm_api_token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->post('https://screenshotmonitor.com/api/v2/GetReport', [
                    'employmentId' => $employmentId,
                    'from' => $from,
                    'to' => $to,
                ]);

                if ($reportResponse->failed()) {
                    throw new \Exception('GetReport failed');
                }

                $data = $reportResponse->json();
                $totalMinutes = 0;

                if (isset($data['charts']['employments']) && is_array($data['charts']['employments'])) {
                    foreach ($data['charts']['employments'] as $record) {
                        $totalMinutes += $record['Duration'] ?? 0;
                    }
                }

                $dailyTimeline = [];
                if (isset($data['charts']['timeline']) && is_array($data['charts']['timeline'])) {
                    foreach ($data['charts']['timeline'] as $record) {
                        $rawDate = $record['Date'] ?? null;
                        $duration = $record['Duration'] ?? 0;
                        if ($rawDate) {
                            try {
                                $dateKey = Carbon::parse($rawDate)->format('Y-m-d');
                                $dailyTimeline[$dateKey] = (int) $duration;
                            } catch (\Exception $e) {
                                // ignore
                            }
                        }
                    }
                }

                return [
                    'total_minutes' => (int) $totalMinutes,
                    'daily_timeline' => $dailyTimeline,
                ];

            } catch (\Exception $e) {
                Log::error('SSM Monthly report data fetch failed for ' . $monthDate->format('Y-m'), ['error' => $e->getMessage()]);
                return [
                    'total_minutes' => 0,
                    'daily_timeline' => [],
                ];
            }
        });
    }

    /**
     * Count Fridays in a month.
     */
    private function countFridaysInMonth(Carbon $date): int
    {
        $count = 0;
        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        $current = $start->copy();
        while ($current->lte($end)) {
            if ($current->isFriday()) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    /**
     * Format minutes to readable duration.
     */
    private function formatDuration(int $minutes): string
    {
        $h = floor($minutes / 60);
        $m = $minutes % 60;

        if ($h > 0 && $m > 0) {
            return "{$h}h {$m}m";
        }
        if ($h > 0) {
            return "{$h}h";
        }
        return "{$m}m";
    }
}
