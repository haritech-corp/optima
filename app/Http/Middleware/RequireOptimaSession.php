<?php

namespace App\Http\Middleware;

use App\Services\SupabaseAuthService;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class RequireOptimaSession
{
    public function __construct(private readonly SupabaseAuthService $supabase) {}

    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();
        $user = $session->get('optima_user');
        if (! $user) {
            return redirect()->route('login')->with('error', 'Silakan masuk untuk melanjutkan.');
        }

        if (($user['expires_at'] ?? 0) <= now()->addMinute()->timestamp) {
            if (empty($user['refresh_token'])) return $this->invalidate($request);
            try {
                $response = $this->supabase->refresh($user['refresh_token']);
            } catch (ConnectionException) {
                return redirect()->route('login')->withErrors(['email' => 'Sesi tidak dapat diperbarui. Silakan masuk kembali.']);
            }
            if ($response->failed() || ! $response->json('access_token')) return $this->invalidate($request);
            $session->put('optima_user', array_merge($user, [
                'access_token' => $response->json('access_token'),
                'refresh_token' => $response->json('refresh_token', $user['refresh_token']),
                'expires_at' => now()->addSeconds((int) $response->json('expires_in', 3600))->timestamp,
            ]));
        }

        return $next($request);
    }

    private function invalidate(Request $request): RedirectResponse
    {
        $request->session()->forget('optima_user');
        return redirect()->route('login')->with('error', 'Sesi Anda telah berakhir. Silakan masuk kembali.');
    }
}
