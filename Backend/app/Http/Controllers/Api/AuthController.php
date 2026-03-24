<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Client;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        [$actor, $actorType] = $this->resolveActor($credentials['login']);

        if (! $actor || ! Hash::check($credentials['password'], $actor->password)) {
            return response()->json(['message' => 'Nepareizi piekļuves dati.'], 422);
        }

        $plainToken = bin2hex(random_bytes(32));

        ApiToken::create([
            'actor_type' => $actorType,
            'actor_id' => $actor->id,
            'token' => hash('sha256', $plainToken),
        ]);

        return response()->json([
            'message' => 'Pieslegsanas veiksmiga.',
            'data' => [
                'token' => $plainToken,
                'actorType' => $actorType,
                'user' => $this->formatActor($actor, $actorType),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('apiToken')?->delete();

        return response()->json(['message' => 'Sesija aizverta.']);
    }

    public function me(Request $request): JsonResponse
    {
        $actor = $this->currentActor($request);
        $actorType = $this->currentActorType($request);

        return response()->json([
            'data' => [
                'actorType' => $actorType,
                'user' => $this->formatActor($actor, $actorType),
            ],
        ]);
    }

    private function resolveActor(string $login): array
    {
        $employee = Employee::query()->where('email', $login)->first();

        if ($employee) {
            return [$employee, 'employee'];
        }

        $client = Client::query()
            ->where('username', $login)
            ->orWhere('email', $login)
            ->first();

        return [$client, 'client'];
    }

    private function formatActor(Client|Employee|null $actor, ?string $actorType): array
    {
        if (! $actor) {
            return [];
        }

        $base = [
            'id' => $actor->id,
            'name' => $actor->name,
            'surname' => $actor->surname,
            'email' => $actor->email,
            'phone' => $actor->phone,
            'actorType' => $actorType,
        ];

        if ($actorType === 'client') {
            return [
                ...$base,
                'username' => $actor->username,
                'street' => $actor->street,
                'homeNumber' => $actor->home_number,
                'flatNumber' => $actor->flat_number,
                'city' => $actor->city,
                'zipCode' => $actor->zip_code,
            ];
        }

        return [
            ...$base,
            'role' => $actor->role,
        ];
    }
}