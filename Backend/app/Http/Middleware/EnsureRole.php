<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $actor = $request->attributes->get('actor');
        $actorType = $request->attributes->get('actorType');

        if (! $actor || ! $actorType) {
            return new JsonResponse(['message' => 'Nepieciešama autorizācija.'], Response::HTTP_UNAUTHORIZED);
        }

        $hasAccess = false;

        foreach ($roles as $role) {
            if ($role === 'client' && $actorType === 'client') {
                $hasAccess = true;
            }

            if ($role === 'employee' && $actorType === 'employee') {
                $hasAccess = true;
            }

            if ($role === 'admin' && $actorType === 'employee' && $actor->role === 'administrators') {
                $hasAccess = true;
            }
        }

        if (! $hasAccess) {
            return new JsonResponse(['message' => 'Jums nav piekļuves šai darbībai.'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}