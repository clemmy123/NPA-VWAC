<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JumuishiClient;
use App\Services\JumuishiUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class JumuishiSsoController extends Controller
{
    public function login(Request $request): RedirectResponse|Response
    {
        if (! config('jumuishi.enabled')) {
            return $this->issue('Jumuishi sign-in is unavailable.', 503);
        }

        return redirect()->away(JumuishiUrl::login());
    }

    public function consume(Request $request, JumuishiClient $client): RedirectResponse|Response
    {
        if (! config('jumuishi.enabled')) {
            return $this->issue('Jumuishi sign-in is unavailable.', 503);
        }

        $validator = Validator::make($request->query(), [
            'ticket' => ['required', 'string', 'size:64'],
            'return_to' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($validator->fails()) {
            return $this->issue('The Jumuishi sign-in ticket is invalid.', 422);
        }

        try {
            $response = $client->exchangeTicket($validator->validated()['ticket']);
        } catch (Throwable $exception) {
            // Do not log HTTP exception messages: they can contain identity data.
            Log::warning('Jumuishi ticket exchange failed.', ['exception' => $exception::class]);

            return $this->issue('The sign-in ticket has expired, was already used, or Jumuishi is unavailable.', 401);
        }

        $identity = $response['data'] ?? null;
        if (($response['status'] ?? null) !== 'success' || ! is_array($identity)
            || Validator::make($identity, [
                'global_user_id' => ['required', 'integer', 'min:1'],
                'email' => ['required', 'email', 'max:255'],
                'status' => ['required', 'in:active'],
            ])->fails()) {
            return $this->issue('Jumuishi did not return an active, valid identity.', 403);
        }

        try {
            $user = DB::transaction(function () use ($identity): ?User {
                $user = User::query()->where('global_user_id', $identity['global_user_id'])->lockForUpdate()->first();
                $user ??= User::query()->whereRaw('LOWER(email) = ?', [strtolower(trim($identity['email']))])
                    ->lockForUpdate()->first();

                if (! $user || ! $user->isActive()
                    || ($user->global_user_id && (string) $user->global_user_id !== (string) $identity['global_user_id'])) {
                    return null;
                }

                $user->forceFill([
                    'global_user_id' => $identity['global_user_id'],
                    'auth_provider' => 'jumuishi',
                    'password_login_enabled' => false,
                    'external_verified_at' => now(),
                ])->save();

                return $user;
            });
        } catch (Throwable $exception) {
            Log::warning('Jumuishi account linking failed.', ['exception' => $exception::class]);

            return $this->issue('Your account could not be linked. Contact the module administrator.', 409);
        }

        if (! $user) {
            return $this->issue('Your module account is missing, inactive, or linked to another identity. Contact the module administrator.', 403);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put('password_hash_web', Auth::guard('web')->hashPasswordForCookie($user->getAuthPassword()));
        $returnTo = $validator->validated()['return_to'] ?? $request->session()->pull('url.intended', '/dashboard');

        return redirect()->to(JumuishiUrl::safeReturnTo($returnTo));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->away(JumuishiUrl::central(config('jumuishi.central_logout_path')));
    }

    private function issue(string $message, int $status): Response
    {
        return response()->view('auth.sso-issue', compact('message', 'status'), $status)
            ->header('Cache-Control', 'no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
