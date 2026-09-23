<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Server-side gateway for Supabase Auth. */
class SupabaseAuthService
{
    public function authorizationUrl(string $redirectTo, string $state, string $codeChallenge): string
    {
        return $this->baseUrl().'/auth/v1/authorize?'.http_build_query([
            'provider' => 'google', 'redirect_to' => $redirectTo, 'state' => $state,
            'code_challenge' => $codeChallenge, 'code_challenge_method' => 's256',
        ]);
    }

    public function exchangeCode(string $code, string $codeVerifier): Response
    {
        return $this->client()->post('/auth/v1/token?grant_type=pkce', ['auth_code' => $code, 'code_verifier' => $codeVerifier]);
    }

    public function passwordSignIn(array $credentials): Response
    {
        return $this->client()->post('/auth/v1/token?grant_type=password', $credentials);
    }

    public function refresh(string $refreshToken): Response
    {
        return $this->client()->post('/auth/v1/token?grant_type=refresh_token', ['refresh_token' => $refreshToken]);
    }

    public function signOut(string $accessToken): Response
    {
        return $this->client($accessToken)->post('/auth/v1/logout');
    }

    private function client(?string $bearer = null): PendingRequest
    {
        $client = Http::baseUrl($this->baseUrl())->acceptJson()->asJson()
            ->withHeader('apikey', (string) config('services.supabase.publishable_key'));

        return $bearer ? $client->withToken($bearer) : $client;
    }

    private function baseUrl(): string
    {
        $url = rtrim((string) config('services.supabase.url'), '/');
        if ($url === '') {
            throw new RuntimeException('SUPABASE_URL belum dikonfigurasi.');
        }
        return $url;
    }
}
