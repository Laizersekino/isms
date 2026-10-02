<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    private const LOGIN_DECAY_SECONDS = 60;

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $rateLimitKey = $this->loginRateLimitKey($credentials['email'], $request);

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_LOGIN_ATTEMPTS)) {
            return $this->failedLoginResponse();
        }

        if (Auth::attempt($credentials)) {
            RateLimiter::clear($rateLimitKey);
            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        }

        RateLimiter::hit($rateLimitKey, self::LOGIN_DECAY_SECONDS);

        return $this->failedLoginResponse();
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    private function loginRateLimitKey(string $email, Request $request): string
    {
        $identifier = Str::lower(trim($email)).'|'.$request->ip();

        return 'login:'.hash('sha256', $identifier);
    }

    private function failedLoginResponse()
    {
        return back()->withErrors([
            'email' => 'The email or password is incorrect.',
        ])->onlyInput('email');
    }
}
