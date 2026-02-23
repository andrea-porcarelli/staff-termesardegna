<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Area;
use App\Models\Department;
use App\Models\MaintenanceRole;
use App\Models\Team;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request) : View
    {
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'created_at');
        $dir = $request->get('direction', 'desc');
        $allowedSorts = ['id', 'name', 'email', 'created_at'];
        if (!in_array($sort, $allowedSorts)) { $sort = 'created_at'; }
        if (!in_array($dir, ['asc', 'desc'])) { $dir = 'desc'; }

        $users = User::when($search, fn($q) => $q->where('name', 'LIKE', "%{$search}%")->orWhere('email', 'LIKE', "%{$search}%"))
            ->orderBy($sort, $dir)
            ->get();

        return view('users.index', compact('users', 'search', 'sort', 'dir'));
    }

    public function create() : View
    {
        $areas = Area::with('departments')->where('active', true)->get();
        $maintenanceRoles = MaintenanceRole::orderBy('name')->get();
        $teams = Team::orderBy('name')->get();
        return view('users.create', compact('areas', 'maintenanceRoles', 'teams'));
    }

    public function store(UserRequest $request) : RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'maintenance_role_id' => $request->role === 'manutentore' ? $request->maintenance_role_id : null,
        ]);

        // Se il ruolo è manutentore, associa i team selezionati
        if ($request->role === 'manutentore' && $request->has('teams')) {
            $user->teams()->sync($request->teams);
        }

        // Se il ruolo è operator o manutentore, associa le zone selezionate
        if ($request->has('departments')) {
            $user->departments()->sync($request->departments);
        }

        return redirect()->route('users.index')
            ->with('success', 'Utente creato con successo!');
    }

    public function edit(User $user) : View
    {
        $areas = Area::with('departments')->where('active', true)->get();
        $maintenanceRoles = MaintenanceRole::orderBy('name')->get();
        $teams = Team::orderBy('name')->get();
        return view('users.edit', compact('user', 'areas', 'maintenanceRoles', 'teams'));
    }

    public function update(UpdateUserRequest $request, User $user) : RedirectResponse
    {
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'maintenance_role_id' => $request->role === 'manutentore' ? $request->maintenance_role_id : null,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Sincronizza zone assegnate
        $user->departments()->sync($request->departments ?? []);

        // Sincronizza team per manutentore
        if ($request->role === 'manutentore') {
            $user->teams()->sync($request->teams ?? []);
        } else {
            $user->teams()->detach();
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
