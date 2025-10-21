<?php
// app/Http/Middleware/CheckUserType.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserType
{
    /**
     * Vérifier le type d'utilisateur
     */
    public function handle(Request $request, Closure $next, string $type)
    {
        if (!$request->user() || $request->user()->type_utilisateur !== $type) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        return $next($request);
    }
}