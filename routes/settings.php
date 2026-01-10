<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance.edit');


});
Route::get('/deploy', function () {
    Artisan::call('optimize:clear');
    Artisan::call('migrate', ['--force' => true]);
    try {
        if (file_exists(public_path('storage'))) {
            try {
                Artisan::call('storage:unlink');
            } catch (\Exception $e) {
                if (is_link(public_path('storage'))) {
                    unlink(public_path('storage'));
                } else {
                    app('files')->deleteDirectory(public_path('storage'));
                }
            }
        }
        Artisan::call('storage:link');
    } catch (\Exception $e) {
   
    }
    Artisan::call('optimize');
    
    return "Deployment commands executed successfully!";
})->name('deploy');
