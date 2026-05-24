<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Vérifie que l'utilisateur connecté possède l'un des rôles autorisés.
     *
     * Usage dans routes/api.php :
     *   ->middleware('role:admin')
     *   ->middleware('role:admin,agent')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        if (!in_array($user->role, $roles)) {
            return response()->json([
                'message' => 'Accès refusé. Rôle requis : ' . implode(' ou ', $roles),
            ], 403);
        }

        return $next($request);
    }
}
