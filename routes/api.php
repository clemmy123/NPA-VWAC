<?php

use App\Http\Controllers\Api\JumuishiUserController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/jumuishi/health', function () {
    try {
        DB::connection()->getPdo();

        return response()->json([
            'status' => 'success', 'module' => config('jumuishi.module_path'),
            'application' => 'running', 'database' => 'connected', 'timestamp' => now()->toIso8601String(),
        ]);
    } catch (Throwable) {
        return response()->json([
            'status' => 'error', 'module' => config('jumuishi.module_path'),
            'application' => 'running', 'database' => 'disconnected', 'timestamp' => now()->toIso8601String(),
        ], 503);
    }
});

Route::middleware(['jumuishi.auth', 'throttle:120,1'])->prefix('jumuishi')->name('jumuishi.')->group(function (): void {
    Route::post('/users/provision', [JumuishiUserController::class, 'provision'])->name('users.provision');
    Route::post('/users/sync', [JumuishiUserController::class, 'sync'])->name('users.sync');
});
