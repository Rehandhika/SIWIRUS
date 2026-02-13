<?php

namespace App\Livewire\Auth;

use App\Jobs\LogLoginActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Login - SIWIRUS')]
#[Layout('layouts.guest')]
class LoginForm extends Component
{
    public $nim = '';

    public $password = '';

    protected $rules = [
        'nim' => 'required|string|min:8|max:20',
        'password' => 'required|string|min:6',
    ];

    protected $messages = [
        'nim.required' => 'NIM wajib diisi',
        'nim.min' => 'NIM minimal 8 karakter',
        'nim.max' => 'NIM maksimal 20 karakter',
        'password.required' => 'Password wajib diisi',
        'password.min' => 'Password minimal 6 karakter',
    ];

    public function login()
    {
        $this->validate();

        // Rate limiting
        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            $this->addError('nim', "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.");

            return;
        }

        // Attempt login
        $credentials = [
            'nim' => $this->nim,
            'password' => $this->password,
            'status' => 'active',
        ];

        if (Auth::attempt($credentials, false)) {
            // Clear rate limiter
            RateLimiter::clear($key);

            // Regenerate session
            session()->regenerate();

            // Dispatch async logging job (non-blocking)
            LogLoginActivity::dispatch(
                Auth::id(),
                request()->ip(),
                request()->userAgent() ?? 'Unknown',
                'success'
            );

            // Redirect to dashboard immediately
            return redirect()->intended(route('admin.dashboard'));
        }

        // Increment rate limiter
        RateLimiter::hit($key, 60);

        // Dispatch async logging for failed attempt (non-blocking)
        LogLoginActivity::dispatch(
            0,
            request()->ip(),
            request()->userAgent() ?? 'Unknown',
            'failed',
            'Invalid credentials or inactive account'
        );

        $this->addError('nim', 'NIM atau password salah, atau akun tidak aktif.');
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->nim).'|'.request()->ip());
    }

    public function render()
    {
        return view('livewire.auth.login-form');
    }
}
