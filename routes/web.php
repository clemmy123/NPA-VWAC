<?php

use App\Http\Controllers\JumuishiSsoController;
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
});
