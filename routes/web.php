<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function(){
    return Inertia::render('Welcome');
})->name('home');


Route::get('dashboard', \App\Http\Controllers\DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Offline Time Tracking Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('offline-time/report', [\App\Http\Controllers\OfflineTimeEntryController::class, 'report'])
        ->name('offline-time.report');

    Route::resource('offline-time', \App\Http\Controllers\OfflineTimeEntryController::class)
        ->parameters(['offline-time' => 'offline_time_entry']);
    
    Route::get('offline-time-summary', [\App\Http\Controllers\OfflineTimeEntryController::class, 'monthlySummary'])
        ->name('offline-time.summary');

    Route::resource('teams', \App\Http\Controllers\TeamController::class);
    Route::post('teams/{team}/members', [\App\Http\Controllers\TeamMemberController::class, 'store'])->name('teams.members.store');
    Route::put('teams/{team}/members/{member}', [\App\Http\Controllers\TeamMemberController::class, 'update'])->name('teams.members.update');
    Route::delete('teams/{team}/members/{member}', [\App\Http\Controllers\TeamMemberController::class, 'destroy'])->name('teams.members.destroy');
});



require __DIR__ . '/settings.php';
