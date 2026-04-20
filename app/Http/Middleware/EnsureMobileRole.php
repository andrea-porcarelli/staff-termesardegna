<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Limita l'accesso al layout mobile (/m) ai ruoli "da campo":
 * manutentore e operator.
 */
class EnsureMobileRole
{
    public const ROLES = ['manutentore', 'operator'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! in_array(auth()->user()->role, self::ROLES, true)) {
            abort(403, 'Accesso riservato ai ruoli operativi.');
        }

        return $next($request);
    }
}
