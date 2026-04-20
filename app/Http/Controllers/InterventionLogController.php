<?php

namespace App\Http\Controllers;

use App\Models\InterventionLog;
use App\Models\User;
use App\Support\InterventionLogActions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InterventionLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_if(Auth::user()->role !== 'admin', 403);

        $query = InterventionLog::with(['intervention:id,title', 'user:id,name'])
            ->orderByDesc('created_at');

        if ($request->filled('intervention_id')) {
            $query->where('intervention_id', $request->integer('intervention_id'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action')->toString());
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->date('from')->startOfDay());
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to')->endOfDay());
        }

        /** @var LengthAwarePaginator $logs */
        $logs = $query->paginate(50)->withQueryString();

        $users = User::orderBy('name')->get(['id', 'name']);
        $actions = InterventionLogActions::all();

        return view('intervention_logs.index', compact('logs', 'users', 'actions'));
    }
}
