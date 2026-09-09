<?php

use App\Http\Controllers\IndicatorBaselineController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\IndicatorTargetController;
use App\Http\Controllers\InterventionController;
use App\Http\Controllers\JumuishiSsoController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ThematicAreaController;
use App\Services\JumuishiUrl;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

Route::get('/login', [JumuishiSsoController::class, 'login'])->name('login');
Route::get('/jumuishi/sso/consume', [JumuishiSsoController::class, 'consume'])
    ->middleware('throttle:30,1')->name('jumuishi.sso.consume');
Route::post('/logout', [JumuishiSsoController::class, 'logout'])->name('logout');

Route::get('/forgot-password', fn () => redirect()->away(JumuishiUrl::central('/forgot-password')))
    ->name('password.request');
Route::get('/reset-password/{token}', fn (string $token) => redirect()->away(
    JumuishiUrl::central('/reset-password/'.rawurlencode($token))
    .(request()->filled('email') ? '?'.http_build_query(['email' => request()->query('email')]) : '')
))->name('password.reset');

Route::middleware(['auth', 'auth.session'])->group(function (): void {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::get('/profile', fn () => redirect()->away(JumuishiUrl::central(config('jumuishi.password_path'))))
        ->name('profile.edit');
    Route::match(['get', 'put'], '/password', fn () => redirect()->away(JumuishiUrl::central(config('jumuishi.password_path'))))
        ->name('password.update');

    Route::apiResource('projects', ProjectController::class)
        ->middlewareFor('index', 'can:project.view')
        ->middlewareFor('show', 'can:project.view')
        ->middlewareFor('store', 'can:project.create')
        ->middlewareFor('update', 'can:project.update')
        ->middlewareFor('destroy', 'can:project.delete');

    Route::apiResource('thematic-areas', ThematicAreaController::class)
        ->middlewareFor('index', 'can:thematic-area.view')
        ->middlewareFor('show', 'can:thematic-area.view')
        ->middlewareFor('store', 'can:thematic-area.create')
        ->middlewareFor('update', 'can:thematic-area.update')
        ->middlewareFor('destroy', 'can:thematic-area.delete');

    Route::apiResource('indicators', IndicatorController::class)
        ->middlewareFor('index', 'can:indicator.view')
        ->middlewareFor('show', 'can:indicator.view')
        ->middlewareFor('store', 'can:indicator.create')
        ->middlewareFor('update', 'can:indicator.update')
        ->middlewareFor('destroy', 'can:indicator.delete');

    Route::apiResource('interventions', InterventionController::class)
        ->middlewareFor('index', 'can:intervention.view')
        ->middlewareFor('show', 'can:intervention.view')
        ->middlewareFor('store', 'can:intervention.create')
        ->middlewareFor('update', 'can:intervention.update')
        ->middlewareFor('destroy', 'can:intervention.delete');

    Route::apiResource('indicator-baselines', IndicatorBaselineController::class)
        ->middlewareFor('index', 'can:indicator.view')
        ->middlewareFor('show', 'can:indicator.view')
        ->middlewareFor('store', 'can:indicator.set-baseline')
        ->middlewareFor('update', 'can:indicator.set-baseline')
        ->middlewareFor('destroy', 'can:indicator.set-baseline');

    Route::apiResource('indicator-targets', IndicatorTargetController::class)
        ->middlewareFor('index', 'can:indicator.view')
        ->middlewareFor('show', 'can:indicator.view')
        ->middlewareFor('store', 'can:indicator.set-target')
        ->middlewareFor('update', 'can:indicator.set-target')
        ->middlewareFor('destroy', 'can:indicator.set-target');
});
