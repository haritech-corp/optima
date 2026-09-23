<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_page_is_available(): void
    {
        $this->get('/login')->assertOk()->assertSee('OPTIMA');
    }

    public function test_google_sso_starts_a_server_side_pkce_flow(): void
    {
        config()->set('services.supabase.url', 'https://project.supabase.co');
        config()->set('services.supabase.publishable_key', 'test-key');

        $response = $this->get('/auth/google');

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('/auth/v1/authorize?', $location);
        $this->assertStringContainsString('provider=google', $location);
        $this->assertStringContainsString('code_challenge_method=s256', $location);
        $response->assertSessionHas('supabase_oauth.state');
        $response->assertSessionHas('supabase_oauth.code_verifier');
    }

    public function test_google_callback_rejects_missing_or_invalid_oauth_state(): void
    {
        $this->get('/auth/callback?code=example&state=invalid')
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
    }
}
