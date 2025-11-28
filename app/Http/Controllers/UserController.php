<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Area;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index() : View
    {
        $users = User::orderBy('created_at', 'desc')->get();
        return view('users.index', compact('users'));
    }

    public function create() : View
    {
        $areas = Area::with('departments')->where('active', true)->get();
        return view('users.create', compact('areas'));
    }

    public function store(UserRequest $request) : RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        // Se il ruolo è supervisor, associa i reparti selezionati
        if ($request->role === 'supervisor' && $request->has('departments')) {
            $user->departments()->sync($request->departments);
        }

        return redirect()->route('users.index')
            ->with('success', 'Utente creato con successo!');
    }

    public function edit(User $user) : View
    {
        $areas = Area::with('departments')->where('active', true)->get();
        return view('users.edit', compact('user', 'areas'));
    }

    public function update(UpdateUserRequest $request, User $user) : RedirectResponse
    {
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Gestisci associazione reparti per supervisor
        if ($request->role === 'supervisor') {
            // Sincronizza i reparti selezionati (o svuota se nessuno selezionato)
            $user->departments()->sync($request->departments ?? []);
        } else {
            // Se il ruolo non è supervisor, rimuovi tutte le associazioni
            $user->departments()->detach();
        }

        return redirect()->route('users.index')
            ->with('success', 'Utente aggiornato con successo!');
    }

    public function destroy(User $user) : RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Non puoi eliminare il tuo account!');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Utente eliminato con successo!');
    }
}
