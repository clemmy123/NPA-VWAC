<?php

use App\Http\Controllers\AdminLocationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DimensionController;
use App\Http\Controllers\DimensionOptionController;
use App\Http\Controllers\FinancialYearController;
use App\Http\Controllers\IndicatorBaselineController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\IndicatorDataAssignmentController;
use App\Http\Controllers\IndicatorDataEntryController;
use App\Http\Controllers\IndicatorTargetController;
use App\Http\Controllers\InterventionController;
use App\Http\Controllers\JumuishiSsoController;
use App\Http\Controllers\LocalAuthController;
use App\Http\Controllers\MeasurementTypeController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationTypeController;
use App\Http\Controllers\PlanBuilderController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectUserController;
use App\Http\Controllers\ReportingPeriodController;
use App\Http\Controllers\ThematicAreaController;
use App\Http\Controllers\ThematicAreaUserController;
use App\Http\Controllers\UnitOfMeasureController;
use App\Http\Controllers\UserController;
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

// Local email/password login for non-SSO organization users (e.g. banks reporting
// data directly) — kept entirely separate from the Jumuishi SSO flow above, which
// is for government-staff single sign-on only.
Route::get('/local-login', [LocalAuthController::class, 'create'])
    ->middleware('guest')->name('local-login');
Route::post('/local-login', [LocalAuthController::class, 'store'])
    ->middleware(['guest', 'throttle:10,1'])->name('local-login.store');
Route::post('/local-logout', [LocalAuthController::class, 'destroy'])
    ->middleware('auth')->name('local-logout');
Route::get('/local-password', [LocalAuthController::class, 'editPassword'])
    ->middleware(['auth', 'auth.session'])->name('local-password.edit');
Route::put('/local-password', [LocalAuthController::class, 'updatePassword'])
    ->middleware(['auth', 'auth.session'])->name('local-password.update');

Route::get('/forgot-password', fn () => redirect()->away(JumuishiUrl::central('/forgot-password')))
    ->name('password.request');
Route::get('/reset-password/{token}', fn (string $token) => redirect()->away(
    JumuishiUrl::central('/reset-password/'.rawurlencode($token))
    .(request()->filled('email') ? '?'.http_build_query(['email' => request()->query('email')]) : '')
))->name('password.reset');

Route::middleware(['auth', 'auth.session'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('admin-locations/{level}', [AdminLocationController::class, 'options'])
        ->name('admin-locations.options');
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

    Route::post('projects/{project}/managers', [ProjectUserController::class, 'store'])
        ->middleware('can:project.assign-manager')->name('projects.managers.store');
    Route::delete('projects/{project}/managers/{user}', [ProjectUserController::class, 'destroy'])
        ->middleware('can:project.assign-manager')->name('projects.managers.destroy');

    Route::get('plan-builder', [PlanBuilderController::class, 'index'])
        ->middleware('can:thematic-area.view')->name('plan-builder.index');
    Route::get('plan-builder/{thematic_area}', [PlanBuilderController::class, 'show'])
        ->middleware('can:thematic-area.view')->name('plan-builder.show');

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

    Route::post('thematic-areas/{thematic_area}/managers', [ThematicAreaUserController::class, 'store'])
        ->middleware('can:thematic-area.assign-manager')->name('thematic-areas.managers.store');
    Route::delete('thematic-areas/{thematic_area}/managers/{user}', [ThematicAreaUserController::class, 'destroy'])
        ->middleware('can:thematic-area.assign-manager')->name('thematic-areas.managers.destroy');

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

    Route::get('indicator-data-entries/create', [IndicatorDataEntryController::class, 'create'])
        ->middleware('can:indicator-data.create')->name('indicator-data-entries.create');
    Route::get('indicator-data-entries/{indicator_data_entry}/edit', [IndicatorDataEntryController::class, 'edit'])
        ->middleware('can:indicator-data.update')->name('indicator-data-entries.edit');

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

    Route::get('indicator-data-entries/{indicator_data_entry}/evidence/{media}', [IndicatorDataEntryController::class, 'downloadEvidence'])
        ->middleware('can:indicator-data.view')
        ->name('indicator-data-entries.evidence.download');

    Route::delete('indicator-data-entries/{indicator_data_entry}/evidence/{media}', [IndicatorDataEntryController::class, 'destroyEvidence'])
        ->middleware('can:indicator-data.update')
        ->name('indicator-data-entries.evidence.destroy');

    // Settings: lookup/reference data management (organizations, financial years,
    // data sources, measurement types, units, disaggregation dimensions). All
    // gated by the single 'settings.manage' permission — these are low-traffic
    // admin screens, not a per-entity permission matrix.
    Route::middleware('can:settings.manage')->group(function (): void {
        Route::get('settings', fn () => view('settings.index'))->name('settings.index');

        Route::resource('organization-types', OrganizationTypeController::class)->except(['show']);
        Route::resource('organizations', OrganizationController::class)->except(['show']);
        Route::resource('financial-years', FinancialYearController::class)->except(['show']);
        Route::resource('reporting-periods', ReportingPeriodController::class)->except(['show']);
        Route::resource('measurement-types', MeasurementTypeController::class)->except(['show']);
        Route::resource('units-of-measure', UnitOfMeasureController::class)
            ->parameters(['units-of-measure' => 'unit_of_measure'])
            ->except(['show']);
        Route::resource('dimensions', DimensionController::class)->except(['show']);

        Route::get('dimensions/{dimension}/options/create', [DimensionOptionController::class, 'create'])->name('dimensions.options.create');
        Route::post('dimensions/{dimension}/options', [DimensionOptionController::class, 'store'])->name('dimensions.options.store');
        Route::get('dimensions/{dimension}/options/{dimension_option}/edit', [DimensionOptionController::class, 'edit'])->name('dimensions.options.edit');
        Route::put('dimensions/{dimension}/options/{dimension_option}', [DimensionOptionController::class, 'update'])->name('dimensions.options.update');
        Route::delete('dimensions/{dimension}/options/{dimension_option}', [DimensionOptionController::class, 'destroy'])->name('dimensions.options.destroy');
    });

    // Users & role assignment.
    Route::get('users/create', [UserController::class, 'create'])
        ->middleware('can:user.create')->name('users.create');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])
        ->middleware('can:user.update')->name('users.edit');

    Route::resource('users', UserController::class)
        ->except(['create', 'edit', 'show'])
        ->middlewareFor('index', 'can:user.view')
        ->middlewareFor('store', 'can:user.create')
        ->middlewareFor('update', 'can:user.update')
        ->middlewareFor('destroy', 'can:user.update');

    // Indicator data assignments: who (which user/organization) reports on which
    // indicator, optionally scoped to a location — this is what lets an
    // organization's own user key in data for only the indicators they own.
    Route::get('indicator-data-assignments/create', [IndicatorDataAssignmentController::class, 'create'])
        ->middleware('can:indicator.assign-user')->name('indicator-data-assignments.create');
    Route::get('indicator-data-assignments/{indicator_data_assignment}/edit', [IndicatorDataAssignmentController::class, 'edit'])
        ->middleware('can:indicator.assign-user')->name('indicator-data-assignments.edit');

    Route::resource('indicator-data-assignments', IndicatorDataAssignmentController::class)
        ->except(['create', 'edit', 'show'])
        ->middleware('can:indicator.assign-user');
});
