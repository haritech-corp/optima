<?php

namespace App\Http\Controllers;

use App\Models\UserProfile;
use App\Services\SupabaseAuthService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class AuthController extends Controller
{
    public function __construct(private readonly SupabaseAuthService $supabase) {}
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        try {
            $response = $this->supabase->passwordSignIn($credentials);
        } catch (ConnectionException) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Layanan autentikasi tidak dapat dihubungi.']);
        }

        if ($response->failed()) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Email atau password tidak valid.']);
        }

        return $this->establishSession($request, $response->json());
    }

    public function google(): RedirectResponse
    {
        $state = Str::random(64);
        $codeVerifier = Str::random(96);
        request()->session()->put('supabase_oauth', ['state' => $state, 'code_verifier' => $codeVerifier]);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
        try {
            $url = $this->supabase->authorizationUrl(route('auth.callback'), $state, $codeChallenge);
        } catch (RuntimeException) {
            return redirect()->route('login')->withErrors(['email' => 'SSO belum dikonfigurasi. Hubungi administrator.']);
        }

        return redirect()->away($url);
    }

    public function callback(Request $request): RedirectResponse
    {
        $oauth = $request->session()->pull('supabase_oauth', []);
        $state = (string) $request->query('state');
        $code = (string) $request->query('code');

        if (! $code || ! isset($oauth['state'], $oauth['code_verifier']) || ! hash_equals($oauth['state'], $state)) {
            return redirect()->route('login')->withErrors(['email' => 'Permintaan SSO tidak valid atau sudah kedaluwarsa. Silakan coba lagi.']);
        }

        try {
            $response = $this->supabase->exchangeCode($code, $oauth['code_verifier']);
        } catch (ConnectionException) {
            return redirect()->route('login')->withErrors(['email' => 'Layanan SSO tidak dapat dihubungi.']);
        }

        if ($response->failed()) {
            return redirect()->route('login')->withErrors(['email' => 'Sesi Google tidak dapat diverifikasi.']);
        }

        return $this->establishSession($request, $response->json());
    }

    public function logout(Request $request): RedirectResponse
    {
        $token = data_get($request->session()->get('optima_user'), 'access_token');
        if ($token) {
            $this->supabase->signOut($token);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda telah keluar.');
    }

    private function establishSession(Request $request, array $auth): RedirectResponse
    {
        $authUser = $auth['user'] ?? [];
        $profile = UserProfile::query()->firstOrCreate(
            ['auth_user_id' => $authUser['id']],
            [
                'name' => data_get($authUser, 'user_metadata.full_name', strtok((string) ($authUser['email'] ?? 'Pengguna'), '@')),
                'email' => $authUser['email'] ?? null,
                'role' => 'crm_staff',
                'is_active' => true,
            ]
        );

        if (! $profile->is_active) {
            return redirect()->route('login')->withErrors(['email' => 'Akun Anda belum aktif. Hubungi administrator.']);
        }

        $request->session()->regenerate();
        $request->session()->put('optima_user', [
            'id' => $profile->id,
            'auth_user_id' => $profile->auth_user_id,
            'name' => $profile->name,
            'email' => $profile->email,
            'role' => $profile->role,
            'access_token' => $auth['access_token'] ?? null,
            'refresh_token' => $auth['refresh_token'] ?? null,
            'expires_at' => now()->addSeconds((int) ($auth['expires_in'] ?? 3600))->timestamp,
        ]);

        return redirect()->intended(route('dashboard'));
    }

}
