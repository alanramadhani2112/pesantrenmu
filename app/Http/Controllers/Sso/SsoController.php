<?php

namespace App\Http\Controllers\Sso;

use App\Http\Controllers\Controller;
use App\Services\Sso\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    private function serverUrl(string $path): string
    {
        return rtrim((string) config('sso.server_url'), '/') . '/' . ltrim($path, '/');
    }

    private function redirectUri(): string
    {
        return config('sso.redirect_uri') ?: route('sso.callback');
    }

    /**
     * Authenticating SSO request to the parent application
     */
    public function preflight(Request $request)
    {
        $request->session()->put('state', $state = Str::random(40));

        $query = http_build_query([
            'client_id' => config('sso.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => '',
            'state' => $state,
        ]);

        Log::info('sso.preflight', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect($this->serverUrl("oauth/authorize?{$query}"));
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

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(config('sso.timeout', 10))
                ->asForm()
                ->post(
                    $this->serverUrl('oauth/token'),
                    [
                        'grant_type' => 'authorization_code',
                        'client_id' => config('sso.client_id'),
                        'client_secret' => config('sso.client_secret'),
                        'redirect_uri' => $this->redirectUri(),
                        'code' => $request->code,
                    ]
                );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('sso.callback_failed', [
                'ip' => $request->ip(),
                'reason' => 'connection_timeout',
                'exception' => $e->getMessage(),
            ]);

            return redirect()->route('login')->with('error', 'Layanan SSO tidak tersedia. Silakan coba lagi nanti.');
        } catch (\Exception $e) {
            Log::error('sso.callback_failed', [
                'ip' => $request->ip(),
                'reason' => 'token_exchange_exception',
                'exception' => $e->getMessage(),
            ]);

            return redirect()->route('login')->with('error', 'Layanan SSO tidak tersedia. Silakan coba lagi nanti.');
        }

        $data = $response->json();

        if (! $response->successful() || empty($data['access_token'])) {
            Log::warning('sso.callback_failed', [
                'ip' => $request->ip(),
                'reason' => 'token_exchange_failed',
                'http_status' => $response->status(),
            ]);

            return redirect()->route('login')->with('error', 'SSO authentication failed, please try again');
        }

        Log::info('sso.callback_success', [
            'ip' => $request->ip(),
        ]);

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

        try {
            $user = UserService::getUser($token);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('SSO server unreachable during user fetch', [
                'exception' => $e->getMessage(),
            ]);

            return redirect()->route('login')->with('error', 'Layanan SSO tidak tersedia. Silakan coba lagi nanti.');
        } catch (\Exception $e) {
            Log::error('SSO user fetch error', [
                'exception' => $e->getMessage(),
            ]);

            return redirect()->route('login')->with('error', 'Layanan SSO tidak tersedia. Silakan coba lagi nanti.');
        }

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
