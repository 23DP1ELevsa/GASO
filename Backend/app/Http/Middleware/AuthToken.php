<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Models\Client;
use App\Models\Employee;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (! $plainToken) {
            return new JsonResponse(['message' => 'Nepieciešama autorizācija.'], Response::HTTP_UNAUTHORIZED);
        }

        $apiToken = ApiToken::query()
            ->where('token', hash('sha256', $plainToken))
            ->first();

        if (! $apiToken) {
            return new JsonResponse(['message' => 'Autorizācijas tokens nav derīgs.'], Response::HTTP_UNAUTHORIZED);
        }

        $actor = match ($apiToken->actor_type) {
            'employee' => Employee::find($apiToken->actor_id),
            'client' => Client::find($apiToken->actor_id),
            default => null,
        };

        if (! $actor) {
            $apiToken->delete();

            return new JsonResponse(['message' => 'Lietotājs vairs nav pieejams.'], Response::HTTP_UNAUTHORIZED);
        }

        $request->attributes->set('actor', $actor);
        $request->attributes->set('actorType', $apiToken->actor_type);
        $request->attributes->set('apiToken', $apiToken);
        $request->setUserResolver(fn () => $actor);

        return $next($request);
    }
}