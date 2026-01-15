<?php

namespace App\Http\Controllers;

use App\Models\OfflineTimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = auth()->user();
        
        // Base query for stats
        $query = OfflineTimeEntry::query();
        
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        // 1. Today's Stats
        $todayMinutes = (clone $query)
            ->whereDate('date', Carbon::today())
            ->sum('duration_minutes');
            
        // 2. This Month's Stats (Offline)
        $monthOfflineMinutes = (clone $query)
            ->whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->sum('duration_minutes');

        // 3. Recent Entries (Last 5)
        $recentEntries = (clone $query)
            ->with(['user:id,name'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->limit(5)
            ->get();

        // 4. Activity Chart Data (Last 7 Days)
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            
            $minutes = (clone $query)
                ->whereDate('date', $date)
                ->sum('duration_minutes');
                
            $chartData[] = [
                'date' => $date->format('M d'),
                'full_date' => $date->format('Y-m-d'),
                'minutes' => (int) $minutes,
                'hours' => round($minutes / 60, 1),
            ];
        }

        // 5. Admin Specific Stats
        $adminStats = [];
        if ($user->isAdmin()) {
            $adminStats = [
                'total_users' => User::count(),
                'active_users_today' => OfflineTimeEntry::whereDate('date', Carbon::today())
                    ->distinct('user_id')
                    ->count(),
            ];
        }

        // 6. Monthly Pace Calculation
        $monthlyPace = $this->calculateMonthlyPace($user, (int) $monthOfflineMinutes);

        return Inertia::render('Dashboard', [
            'stats' => [
                'today_minutes' => (int) $todayMinutes,
                'today_formatted' => $this->formatDuration((int) $todayMinutes),
                'month_minutes' => (int) $monthOfflineMinutes,
                'month_formatted' => $this->formatDuration((int) $monthOfflineMinutes),
            ],
            'recentEntries' => $recentEntries,
            'chartData' => $chartData,
            'adminStats' => $adminStats,
            'isAdmin' => $user->isAdmin(),
            'monthlyPace' => $monthlyPace,
        ]);
    }

    /**
     * Calculate monthly pace - how much work per day to meet target.
     */
    private function calculateMonthlyPace($user, int $offlineMinutes): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        
        // 1. Calculate total working days in month (excluding Fridays)
        $totalDaysInMonth = $endOfMonth->day;
        $fridaysInMonth = $this->countFridaysInRange($startOfMonth, $endOfMonth);
        $totalWorkingDays = $totalDaysInMonth - $fridaysInMonth;
        
        // Monthly target: 8 hours per working day
        $monthlyTargetMinutes = $totalWorkingDays * 8 * 60;
        
        // 2. Get SSM online minutes for month (cached for 30 mins)
        $ssmMonthMinutes = $this->getSSMMonthlyMinutes($user);
        
        // 3. Total worked this month
        $totalWorkedMinutes = $offlineMinutes + $ssmMonthMinutes;
        
        // 4. Calculate remaining
        $remainingMinutes = max(0, $monthlyTargetMinutes - $totalWorkedMinutes);
        
        // 5. Calculate remaining working days (from tomorrow, excluding Fridays)
        $tomorrow = $now->copy()->addDay()->startOfDay();
        $remainingWorkingDays = 0;
        
        if ($tomorrow->lte($endOfMonth)) {
            // Iterative approach to be absolutely safe and accurate
            $current = $tomorrow->copy();
            while ($current->lte($endOfMonth)) {
                if (!$current->isFriday()) {
                    $remainingWorkingDays++;
                }
                $current->addDay();
            }
        }
        
        // 6. Required daily pace
        $requiredDailyMinutes = $remainingWorkingDays > 0 
            ? (int) ceil($remainingMinutes / $remainingWorkingDays) 
            : 0; // If 0 days left, required is 0 (handled by status)
        
        // 7. Determine status
        $status = 'on_track';
        if ($remainingMinutes <= 0) {
            $status = 'completed';
        } elseif ($remainingWorkingDays === 0 && $remainingMinutes > 0) {
            $status = 'missed';
        } elseif ($requiredDailyMinutes > 10 * 60) { // More than 10h/day needed
            $status = 'behind';
        }
        
        return [
            'monthly_target_minutes' => $monthlyTargetMinutes,
            'monthly_target_formatted' => $this->formatDuration($monthlyTargetMinutes),
            'total_worked_minutes' => $totalWorkedMinutes,
            'total_worked_formatted' => $this->formatDuration($totalWorkedMinutes),
            'offline_minutes' => $offlineMinutes,
            'online_minutes' => $ssmMonthMinutes,
            'remaining_minutes' => $remainingMinutes,
            'remaining_formatted' => $this->formatDuration($remainingMinutes),
            'remaining_working_days' => $remainingWorkingDays,
            'total_working_days' => $totalWorkingDays,
            'required_daily_minutes' => $requiredDailyMinutes,
            'required_daily_formatted' => $this->formatDuration($requiredDailyMinutes),
            'status' => $status,
            'ssm_configured' => !empty($user->ssm_api_token),
        ];
    }

    /**
     * Count Fridays in a date range.
     */
    private function countFridaysInRange(Carbon $start, Carbon $end): int
    {
        $count = 0;
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
     * Get SSM monthly minutes with 30-minute cache.
     */
    private function getSSMMonthlyMinutes($user): int
    {
        if (empty($user->ssm_api_token)) {
            return 0;
        }

        $cacheKey = 'ssm_monthly_' . $user->id . '_' . Carbon::now()->format('Y-m');
        
        return Cache::remember($cacheKey, 1800, function () use ($user) {
            try {
                return $this->fetchSSMMonthlyReport($user->ssm_api_token);
            } catch (\Exception $e) {
                Log::error('SSM Monthly fetch failed', ['error' => $e->getMessage()]);
                return 0;
            }
        });
    }

    /**
     * Fetch monthly report from SSM API.
     */
    private function fetchSSMMonthlyReport(string $apiToken): int
    {
        $now = Carbon::now();
        $from = $now->copy()->startOfMonth()->format('Y-m-d');
        $to = $now->format('Y-m-d');

        // First get employmentId
        $commonResponse = Http::withoutVerifying()->withHeaders([
            'X-SSM-Token' => $apiToken,
            'Accept' => 'application/json',
        ])->get('https://screenshotmonitor.com/api/v2/GetCommonData');

        if ($commonResponse->failed()) {
            throw new \Exception('GetCommonData failed');
        }

        $employmentId = $commonResponse->json('employmentId');
        
        if (!$employmentId) {
            throw new \Exception('No employmentId found');
        }

        // Fetch monthly report
        $reportResponse = Http::withoutVerifying()->withHeaders([
            'X-SSM-Token' => $apiToken,
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
        
        // Parse from charts.employments (PascalCase Duration)
        if (isset($data['charts']['employments']) && is_array($data['charts']['employments'])) {
            foreach ($data['charts']['employments'] as $record) {
                $totalMinutes += $record['Duration'] ?? 0;
            }
        }
        
        Log::info('SSM Monthly Report', ['from' => $from, 'to' => $to, 'totalMinutes' => $totalMinutes]);
        
        return $totalMinutes;
    }

    private function formatDuration(int $minutes): string
    {
        $h = floor($minutes / 60);
        $m = $minutes % 60;
        
        if ($h > 0 && $m > 0) return "{$h}h {$m}m";
        if ($h > 0) return "{$h}h";
        return "{$m}m";
    }
}
