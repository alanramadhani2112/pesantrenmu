<?php

namespace App\Http\Controllers\Sso;

use App\Http\Controllers\Controller;
use App\Services\Sso\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    /**
     * Authenticating SSO request to the parent application
     */
    public function preflight(Request $request)
    {
        $request->session()->put('state', $state = Str::random(40));

        $query = http_build_query([
            'client_id' => config('sso.client_id'),
            'redirect_uri' => route('sso.callback'),
            'response_type' => 'code',
            'scope' => '',
            'state' => $state,
        ]);

        return redirect(config('sso.server_url')."oauth/authorize?{$query}");
    }

    /**
     * Authenticating user
     */
    public function auth(Request $request)
    {
        $state = session()->pull('state');

        try {
            throw_unless(
                strlen($state) > 0 && $state === $request->state,
                \InvalidArgumentException::class
            );
        } catch (\InvalidArgumentException $e) {
            Log::error('Invalid state');

            return redirect()->route('login')->with('error', 'Invalid state, it may be expired, please try again');
        }

        $response = \Illuminate\Support\Facades\Http::asForm()->post(
            config('sso.server_url').'oauth/token',
            [
                'grant_type' => 'authorization_code',
                'client_id' => config('sso.client_id'),
                'client_secret' => config('sso.client_secret'),
                'redirect_uri' => route('sso.callback'),
                'code' => $request->code,
            ]
        );

        $data = $response->json();

        if (! $response->successful() || empty($data['access_token'])) {
            Log::error('SSO token exchange failed', ['status' => $response->status()]);

            return redirect()->route('login')->with('error', 'SSO authentication failed, please try again');
        }

        $request->session()->put('sso_access_token', $data['access_token']);

        return redirect()->route('sso.login');
    }

    public function login(Request $request)
    {
        $token = $request->session()->pull('sso_access_token');

        if (empty($token)) {
            Log::error('SSO login called without token in session');

            return redirect()->route('login')->with('error', 'SSO session expired, please try again');
        }

        $user = UserService::getUser($token);

        if (empty($user)) {
            Log::error('User not found');

            return redirect(route('login'))->with('error', 'The credential is invalid, please use another user');
        }

        if ($user->status == 0) {
            return redirect(route('login'))->with('error', 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.');
        }

        \Illuminate\Support\Facades\Auth::login($user);

        $url = session()->pull('intended_url');

        if ($url) {
            return redirect($url);
        }

        return redirect(config('sso.redirect_url'));
    }
}
