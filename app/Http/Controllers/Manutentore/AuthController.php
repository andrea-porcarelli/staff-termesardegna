<?php

namespace App\Http\Controllers\Manutentore;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureMobileRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Login a 2 step per l'area mobile (/m), riservato a operator/manutentore.
 *
 * Step 1: l'utente inserisce la mail.
 *  - se set_password=false → step "imposta password"
 *  - se set_password=true  → step "password"
 * Una volta impostata la password, il flag set_password viene messo a true
 * e dal login successivo si va direttamente a step 2.
 */
class AuthController extends Controller
{
    private const SESSION_EMAIL = 'm_login_email';

    public function showEmail(): View|RedirectResponse
    {
        if (Auth::check() && in_array(Auth::user()->role, EnsureMobileRole::ROLES, true)) {
            return redirect()->route('m.home');
        }

        return view('auth.manutentore.email');
    }

    public function submitEmail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Inserisci la tua email.',
            'email.email' => 'Email non valida.',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user
            || ! in_array($user->role, EnsureMobileRole::ROLES, true)
            || ! $user->active) {
            return back()
                ->withErrors(['email' => 'Email non riconosciuta o account non attivo.'])
                ->withInput();
        }

        $request->session()->put(self::SESSION_EMAIL, $user->email);

        return $user->set_password
            ? redirect()->route('m.login.password')
            : redirect()->route('m.login.set');
    }

    public function showPassword(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_EMAIL);
        if (! $email) {
            return redirect()->route('m.login');
        }

        return view('auth.manutentore.password', ['email' => $email]);
    }

    public function submitPassword(Request $request): RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_EMAIL);
        if (! $email) {
            return redirect()->route('m.login');
        }

        $data = $request->validate([
            'password' => 'required|string',
        ], [
            'password.required' => 'Inserisci la password.',
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt(['email' => $email, 'password' => $data['password'], 'active' => true], $remember)) {
            return back()->withErrors(['password' => 'Password non corretta.']);
        }

        $user = Auth::user();
        if (! in_array($user->role, EnsureMobileRole::ROLES, true)) {
            Auth::logout();

            return redirect()->route('m.login')
                ->withErrors(['email' => 'Account non abilitato all\'area mobile.']);
        }

        $request->session()->forget(self::SESSION_EMAIL);
        $request->session()->regenerate();

        return redirect()->intended(route('m.home'));
    }

    public function showSet(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_EMAIL);
        if (! $email) {
            return redirect()->route('m.login');
        }

        return view('auth.manutentore.set', ['email' => $email]);
    }

    public function submitSet(Request $request): RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_EMAIL);
        if (! $email) {
            return redirect()->route('m.login');
        }

        $data = $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.required' => 'Inserisci la password.',
            'password.min' => 'La password deve contenere almeno 6 caratteri.',
            'password.confirmed' => 'Le due password non coincidono.',
        ]);

        $user = User::where('email', $email)->first();

        if (! $user
            || ! in_array($user->role, EnsureMobileRole::ROLES, true)
            || ! $user->active
            || $user->set_password) {
            $request->session()->forget(self::SESSION_EMAIL);

            return redirect()->route('m.login')
                ->withErrors(['email' => 'Operazione non valida. Riprova ad accedere.']);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'set_password' => true,
        ])->save();

        Auth::login($user);

        $request->session()->forget(self::SESSION_EMAIL);
        $request->session()->regenerate();

        return redirect()->route('m.home');
    }
}
