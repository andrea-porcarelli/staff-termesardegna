<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordSetController extends Controller
{
    public function show(Request $request, string $token): View
    {
        return view('auth.set-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $request->merge(['token' => $token]);

        $data = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.min' => 'La password deve contenere almeno 6 caratteri.',
            'password.confirmed' => 'Le due password non coincidono.',
        ]);

        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('success', 'Password impostata. Ora puoi accedere.');
        }

        return back()
            ->withErrors(['email' => 'Link non valido o scaduto. Chiedi all\'amministratore di reinviarti l\'email.'])
            ->withInput($request->only('email'));
    }
}
