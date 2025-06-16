<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckEmployeePermissions
{
    private array $restrictedEndpoints = [
        'employees',
        'debt-payments'
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user instanceof \App\Models\Employee) {
            return $next($request);
        }

        $path = $request->path();

        foreach ($this->restrictedEndpoints as $endpoint) {
            if (str_contains($path, $endpoint)) {
                return response()->json([
                    'message' => 'No tienes permiso para acceder a este recurso'
                ], 403);
            }
        }

        return $next($request);
    }
}