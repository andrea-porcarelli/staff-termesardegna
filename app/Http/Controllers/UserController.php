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
use App\Models\WorkScheduleSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
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
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        // Se il ruolo è manutentore, associa specializzazioni (con livello) e team
        if ($request->role === 'manutentore') {
            $syncData = [];
            $levels = $request->maintenance_role_levels ?? [];
            foreach ($request->maintenance_roles ?? [] as $roleId) {
                $syncData[$roleId] = ['level' => max(1, min(5, (int)($levels[$roleId] ?? 1)))];
            }
            $user->maintenanceRoles()->sync($syncData);
            $user->teams()->sync($request->teams ?? []);
        }

        // Aree: solo per operator
        $user->areas()->sync($request->role === 'operator' ? ($request->areas ?? []) : []);
        // Zone: per operator e manutentore
        if (in_array($request->role, ['operator', 'manutentore'])) {
            $user->departments()->sync($request->departments ?? []);
        }

        return redirect()->route('users.index')
            ->with('success', 'Utente creato con successo!');
    }

    public function edit(User $user) : View
    {
        $areas = Area::with('departments')->where('active', true)->get();
        $maintenanceRoles = MaintenanceRole::orderBy('name')->get();
        $teams = Team::orderBy('name')->get();

        $existingSlots = old('schedule_slots',
            $user->workScheduleSlots->map(fn($s) => [
                'date'         => $s->date?->format('Y-m-d') ?? '',
                'day_of_week'  => $s->day_of_week !== null ? (string) $s->day_of_week : '',
                'start_time'   => $s->start_time ? substr($s->start_time, 0, 5) : '',
                'end_time'     => $s->end_time ? substr($s->end_time, 0, 5) : '',
                'type'         => $s->type,
                'is_recurring' => $s->is_recurring ? '1' : '0',
            ])->toArray()
        );

        return view('users.edit', compact('user', 'areas', 'maintenanceRoles', 'teams', 'existingSlots'));
    }

    public function update(UpdateUserRequest $request, User $user) : RedirectResponse
    {
        $data = [
            'name'  => $request->name,
            'email' => $request->email,
            'role'  => $request->role,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Aree: solo per operator
        $user->areas()->sync($request->role === 'operator' ? ($request->areas ?? []) : []);
        // Zone: per operator e manutentore
        if (in_array($request->role, ['operator', 'manutentore'])) {
            $user->departments()->sync($request->departments ?? []);
        } else {
            $user->departments()->detach();
        }

        // Sincronizza specializzazioni (con livello) e team per manutentore
        if ($request->role === 'manutentore') {
            $syncData = [];
            $levels = $request->maintenance_role_levels ?? [];
            foreach ($request->maintenance_roles ?? [] as $roleId) {
                $syncData[$roleId] = ['level' => max(1, min(5, (int)($levels[$roleId] ?? 1)))];
            }
            $user->maintenanceRoles()->sync($syncData);
            $user->teams()->sync($request->teams ?? []);
        } else {
            $user->maintenanceRoles()->detach();
            $user->teams()->detach();
        }

        // Se il ruolo cambia da manutentore, pulisci il piano orario
        if ($request->role !== 'manutentore') {
            $user->workScheduleSlots()->delete();
        }

        return redirect()->route('users.index')
            ->with('success', 'Utente aggiornato con successo!');
    }

    public function updateSchedule(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'slots'                => 'nullable|array',
            'slots.*.start_time'   => 'nullable|date_format:H:i',
            'slots.*.end_time'     => 'nullable|date_format:H:i',
            'slots.*.type'         => 'nullable|in:lavorativo,ferie,riposi,pausa_pranzo',
            'slots.*.is_recurring' => 'nullable|in:0,1',
            'slots.*.day_of_week'  => 'nullable|integer|between:0,6',
            'slots.*.date'         => 'nullable|date',
        ]);

        $user->workScheduleSlots()->delete();

        foreach ($request->get('slots', []) as $slot) {
            $type = in_array($slot['type'] ?? '', ['lavorativo', 'ferie', 'riposi', 'pausa_pranzo'])
                ? $slot['type']
                : 'lavorativo';
            $isAllDay = in_array($type, ['ferie', 'riposi']) && empty($slot['start_time']);
            if (!$isAllDay && (empty($slot['start_time']) || empty($slot['end_time']))) {
                continue;
            }
            $isRecurring = (bool)(int)($slot['is_recurring'] ?? 0);
            $user->workScheduleSlots()->create([
                'date'         => !$isRecurring ? ($slot['date'] ?: null) : null,
                'day_of_week'  => $isRecurring  ? (int)($slot['day_of_week'] ?? 0) : null,
                'start_time'   => $slot['start_time'] ?: null,
                'end_time'     => $slot['end_time'] ?: null,
                'type'         => $type,
                'is_recurring' => $isRecurring,
            ]);
        }

        return response()->json(['message' => 'Piano orario salvato con successo.']);
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

    public function impersonate(User $user) : RedirectResponse
    {
        // Non permettere di impersonificare se già sta impersonificando
        if (session()->has('impersonating_from')) {
            return back()->with('error', 'Devi tornare all\'account precedente prima di impersonificare un altro utente.');
        }

        // Non permettere di impersonificare se stessi
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Non puoi impersonificare te stesso!');
        }

        // Salva l'ID dell'admin che sta impersonificando
        session(['impersonating_from' => auth()->id()]);
        session(['impersonating_from_name' => auth()->user()->name]);

        // Accedi come l'utente
        auth()->login($user);

        return redirect()->route('dashboard')
            ->with('success', 'Stai impersonificando ' . $user->name);
    }

    public function stopImpersonating() : RedirectResponse
    {
        // Recupera l'ID dell'admin originale
        $adminId = session('impersonating_from');

        if (!$adminId) {
            return back()->with('error', 'Non sei in modalità impersonificazione.');
        }

        // Rimuovi la sessione di impersonificazione
        session()->forget(['impersonating_from', 'impersonating_from_name']);

        // Accedi di nuovo come l'admin
        $admin = User::findOrFail($adminId);
        auth()->login($admin);

        return redirect()->route('users.index')
            ->with('success', 'Sei tornato al tuo account.');
    }
}
