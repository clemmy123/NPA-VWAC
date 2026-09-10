<?php

use App\Http\Controllers\IndicatorBaselineController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\IndicatorDataEntryController;
use App\Http\Controllers\IndicatorTargetController;
use App\Http\Controllers\InterventionController;
use App\Http\Controllers\JumuishiSsoController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ThematicAreaController;
use App\Models\User;
use App\Services\JumuishiUrl;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

Route::redirect('/', '/dashboard');

if (app()->environment('local')) {
    // Local-only convenience: Jumuishi SSO can't be exercised against a local
    // instance (there's no central hub to redirect to), so this logs in the
    // first Super Admin (creating one via RolePermissionSeeder's roles if
    // needed) to let the shell be reviewed in a browser without real SSO.
    Route::get('/dev-login', function () {
        if (! Role::where('name', 'Super Admin')->exists()) {
            Artisan::call('db:seed', [
                '--class' => RolePermissionSeeder::class,
                '--force' => true,
            ]);
        }

        $user = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'Super Admin'))->first();

        if (! $user) {
            $user = User::factory()->create();
            $user->assignRole('Super Admin');
        }

        Auth::login($user);

        return redirect('/dashboard');
    })->name('dev-login');
}

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

    Route::get('projects/create', [ProjectController::class, 'create'])
        ->middleware('can:project.create')->name('projects.create');
    Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])
        ->middleware('can:project.update')->name('projects.edit');

    Route::apiResource('projects', ProjectController::class)
        ->middlewareFor('index', 'can:project.view')
        ->middlewareFor('show', 'can:project.view')
        ->middlewareFor('store', 'can:project.create')
        ->middlewareFor('update', 'can:project.update')
        ->middlewareFor('destroy', 'can:project.delete');

    Route::get('thematic-areas/create', [ThematicAreaController::class, 'create'])
        ->middleware('can:thematic-area.create')->name('thematic-areas.create');
    Route::get('thematic-areas/{thematic_area}/edit', [ThematicAreaController::class, 'edit'])
        ->middleware('can:thematic-area.update')->name('thematic-areas.edit');

    Route::apiResource('thematic-areas', ThematicAreaController::class)
        ->middlewareFor('index', 'can:thematic-area.view')
        ->middlewareFor('show', 'can:thematic-area.view')
        ->middlewareFor('store', 'can:thematic-area.create')
        ->middlewareFor('update', 'can:thematic-area.update')
        ->middlewareFor('destroy', 'can:thematic-area.delete');

    Route::get('indicators/create', [IndicatorController::class, 'create'])
        ->middleware('can:indicator.create')->name('indicators.create');
    Route::get('indicators/{indicator}/edit', [IndicatorController::class, 'edit'])
        ->middleware('can:indicator.update')->name('indicators.edit');

    Route::apiResource('indicators', IndicatorController::class)
        ->middlewareFor('index', 'can:indicator.view')
        ->middlewareFor('show', 'can:indicator.view')
        ->middlewareFor('store', 'can:indicator.create')
        ->middlewareFor('update', 'can:indicator.update')
        ->middlewareFor('destroy', 'can:indicator.delete');

    Route::get('interventions/create', [InterventionController::class, 'create'])
        ->middleware('can:intervention.create')->name('interventions.create');
    Route::get('interventions/{intervention}/edit', [InterventionController::class, 'edit'])
        ->middleware('can:intervention.update')->name('interventions.edit');

    Route::apiResource('interventions', InterventionController::class)
        ->middlewareFor('index', 'can:intervention.view')
        ->middlewareFor('show', 'can:intervention.view')
        ->middlewareFor('store', 'can:intervention.create')
        ->middlewareFor('update', 'can:intervention.update')
        ->middlewareFor('destroy', 'can:intervention.delete');

    Route::get('indicator-baselines/create', [IndicatorBaselineController::class, 'create'])
        ->middleware('can:indicator.set-baseline')->name('indicator-baselines.create');
    Route::get('indicator-baselines/{indicator_baseline}/edit', [IndicatorBaselineController::class, 'edit'])
        ->middleware('can:indicator.set-baseline')->name('indicator-baselines.edit');

    Route::apiResource('indicator-baselines', IndicatorBaselineController::class)
        ->middlewareFor('index', 'can:indicator.view')
        ->middlewareFor('show', 'can:indicator.view')
        ->middlewareFor('store', 'can:indicator.set-baseline')
        ->middlewareFor('update', 'can:indicator.set-baseline')
        ->middlewareFor('destroy', 'can:indicator.set-baseline');

    Route::get('indicator-targets/create', [IndicatorTargetController::class, 'create'])
        ->middleware('can:indicator.set-target')->name('indicator-targets.create');
    Route::get('indicator-targets/{indicator_target}/edit', [IndicatorTargetController::class, 'edit'])
        ->middleware('can:indicator.set-target')->name('indicator-targets.edit');

    Route::apiResource('indicator-targets', IndicatorTargetController::class)
        ->middlewareFor('index', 'can:indicator.view')
        ->middlewareFor('show', 'can:indicator.view')
        ->middlewareFor('store', 'can:indicator.set-target')
        ->middlewareFor('update', 'can:indicator.set-target')
        ->middlewareFor('destroy', 'can:indicator.set-target');

    Route::apiResource('indicator-data-entries', IndicatorDataEntryController::class)
        ->only(['index', 'store', 'show', 'update'])
        ->middlewareFor('index', 'can:indicator-data.view')
        ->middlewareFor('show', 'can:indicator-data.view')
        ->middlewareFor('store', 'can:indicator-data.create')
        ->middlewareFor('update', 'can:indicator-data.update');

    Route::post('indicator-data-entries/{indicator_data_entry}/submit', [IndicatorDataEntryController::class, 'submit'])
        ->middleware('can:indicator-data.submit')
        ->name('indicator-data-entries.submit');

    Route::post('indicator-data-entries/{indicator_data_entry}/approve', [IndicatorDataEntryController::class, 'approve'])
        ->middleware('can:indicator-data.approve')
        ->name('indicator-data-entries.approve');

    Route::post('indicator-data-entries/{indicator_data_entry}/return', [IndicatorDataEntryController::class, 'returnEntry'])
        ->middleware('can:indicator-data.return')
        ->name('indicator-data-entries.return');
});
