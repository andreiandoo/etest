<?php

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function mount(): void
    {
        $redirect = request()->query('redirect');

        if (is_string($redirect) && str_starts_with($redirect, url('/'))) {
            request()->session()->put('url.intended', $redirect);
        }
    }

    public function authenticate(): void
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $this->remember)) {
            $this->addError('email', 'Datele de autentificare nu sunt corecte.');

            return;
        }

        request()->session()->regenerate();

        Auth::user()?->forceFill(['last_login_at' => now()])->save();

        $this->redirectIntended(default: route('dashboard'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
