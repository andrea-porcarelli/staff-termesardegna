<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route($this->homeRoute());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        if (Auth::attempt([...$credentials, 'active' => true], $remember)) {
            $request->session()->regenerate();

            return redirect()->intended(route($this->homeRoute()));
        }

        $user = \App\Models\User::where('email', $credentials['email'])->first();
        if ($user && ! $user->active) {
            return back()
                ->withErrors(['email' => 'Account disattivato. Contatta un amministratore.'])
                ->withInput($request->only('email'));
        }

        return back()
            ->withErrors(['email' => 'Le credenziali fornite non sono corrette.'])
            ->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function homeRoute(): string
    {
        return in_array(Auth::user()?->role, \App\Http\Middleware\EnsureMobileRole::ROLES, true)
            ? 'm.home'
            : 'dashboard';
    }
}
