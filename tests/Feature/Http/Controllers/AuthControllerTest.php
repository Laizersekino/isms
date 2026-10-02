<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private const MAX_LOGIN_ATTEMPTS = 5;

    private const LOGIN_DECAY_SECONDS = 60;

    public function test_valid_credentials_log_in_and_redirect_to_the_dashboard(): void
    {
        $user = $this->createUser('alex@example.test', 'correct-password');

        $response = $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_return_the_existing_login_error(): void
    {
        $this->createUser('alex@example.test', 'correct-password');

        $response = $this->from(route('login'))->post(route('login.submit'), [
            'email' => 'alex@example.test',
            'password' => 'incorrect-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'The email or password is incorrect.',
        ]);
        $response->assertSessionHas('_old_input.email', 'alex@example.test');
        $this->assertGuest();
    }

    public function test_repeated_failed_attempts_are_throttled_after_five_failures(): void
    {
        for ($attempt = 0; $attempt < self::MAX_LOGIN_ATTEMPTS; $attempt++) {
            $this->from(route('login'))->post(route('login.submit'), [
                'email' => 'alex@example.test',
                'password' => 'incorrect-password',
            ])->assertSessionHasErrors('email');
        }

        $response = $this->from(route('login'))->post(route('login.submit'), [
            'email' => 'alex@example.test',
            'password' => 'incorrect-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'The email or password is incorrect.',
        ]);
        $this->assertSame(
            self::MAX_LOGIN_ATTEMPTS,
            RateLimiter::attempts($this->limiterKey('alex@example.test', '127.0.0.1'))
        );
    }

    public function test_throttled_attempt_is_rejected_before_authentication_is_attempted(): void
    {
        $key = $this->limiterKey('alex@example.test', '127.0.0.1');
        RateLimiter::hit($key, self::LOGIN_DECAY_SECONDS);

        for ($attempt = 1; $attempt < self::MAX_LOGIN_ATTEMPTS; $attempt++) {
            RateLimiter::hit($key, self::LOGIN_DECAY_SECONDS);
        }

        Auth::shouldReceive('attempt')->never();

        $response = $this->from(route('login'))->post(route('login.submit'), [
            'email' => 'alex@example.test',
            'password' => 'incorrect-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'The email or password is incorrect.',
        ]);
    }

    public function test_successful_login_clears_failed_attempts_for_that_email_and_ip(): void
    {
        $user = $this->createUser('alex@example.test', 'correct-password');
        $key = $this->limiterKey('alex@example.test', '127.0.0.1');

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post(route('login.submit'), [
                'email' => 'alex@example.test',
                'password' => 'incorrect-password',
            ])->assertSessionHasErrors('email');
        }

        $response = $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertSame(0, RateLimiter::attempts($key));
        $this->assertAuthenticatedAs($user);
    }

    public function test_failed_attempts_are_isolated_by_client_ip_address(): void
    {
        $this->postFromIp('alex@example.test', 'wrong-password', '192.0.2.10')
            ->assertSessionHasErrors('email');
        $this->postFromIp('alex@example.test', 'wrong-password', '192.0.2.11')
            ->assertSessionHasErrors('email');

        $this->assertSame(
            1,
            RateLimiter::attempts($this->limiterKey('alex@example.test', '192.0.2.10'))
        );
        $this->assertSame(
            1,
            RateLimiter::attempts($this->limiterKey('alex@example.test', '192.0.2.11'))
        );
    }

    public function test_failed_attempts_are_isolated_by_normalized_email_address(): void
    {
        $this->post(route('login.submit'), [
            'email' => 'alex@example.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
        $this->post(route('login.submit'), [
            'email' => 'jordan@example.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
        $this->post(route('login.submit'), [
            'email' => 'ALEX@EXAMPLE.TEST',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertSame(
            2,
            RateLimiter::attempts($this->limiterKey('alex@example.test', '127.0.0.1'))
        );
        $this->assertSame(
            1,
            RateLimiter::attempts($this->limiterKey('jordan@example.test', '127.0.0.1'))
        );
    }

    public function test_failed_login_does_not_reveal_whether_the_email_account_exists(): void
    {
        $this->createUser('known@example.test', 'correct-password');

        $knownEmailResponse = $this->from(route('login'))->post(route('login.submit'), [
            'email' => 'known@example.test',
            'password' => 'wrong-password',
        ]);
        $unknownEmailResponse = $this->from(route('login'))->post(route('login.submit'), [
            'email' => 'unknown@example.test',
            'password' => 'wrong-password',
        ]);

        $knownEmailResponse->assertSessionHasErrors([
            'email' => 'The email or password is incorrect.',
        ]);
        $unknownEmailResponse->assertSessionHasErrors([
            'email' => 'The email or password is incorrect.',
        ]);
        $this->assertSame(
            $knownEmailResponse->getSession()->get('errors')->getBag('default')->first('email'),
            $unknownEmailResponse->getSession()->get('errors')->getBag('default')->first('email')
        );
    }

    public function test_successful_login_regenerates_the_session_identifier(): void
    {
        $user = $this->createUser('alex@example.test', 'correct-password');
        $this->get(route('login'));
        $session = $this->app['session']->driver();
        $initialSessionId = $session->getId();

        $response = $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertNotSame($initialSessionId, $session->getId());
    }

    public function test_logout_invalidates_the_session_and_redirects_to_login(): void
    {
        $user = $this->createUser('alex@example.test', 'correct-password');
        $this->actingAs($user)->withSession(['logout-test-value' => 'present']);

        $response = $this->post(route('logout'));

        $response->assertRedirect('/login');
        $response->assertSessionMissing('logout-test-value');
        $this->assertGuest();
    }

    private function createUser(string $email, string $password): User
    {
        return User::factory()->create([
            'email' => $email,
            'password' => Hash::make($password),
        ]);
    }

    private function postFromIp(string $email, string $password, string $ip)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post(route('login.submit'), [
                'email' => $email,
                'password' => $password,
            ]);
    }

    private function limiterKey(string $email, string $ip): string
    {
        return 'login:'.hash('sha256', Str::lower(trim($email)).'|'.$ip);
    }
}
