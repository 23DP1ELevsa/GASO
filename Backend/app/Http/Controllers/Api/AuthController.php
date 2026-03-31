<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Client;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function adminRegistrationStatus(): JsonResponse
    {
        return response()->json([
            'data' => [
                'available' => $this->adminRegistrationAvailable(),
            ],
        ]);
    }

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

        return response()->json([
            'message' => 'Pieslēgšanās veiksmīga.',
            'data' => $this->createSessionPayload($actor, $actorType),
        ]);
    }

    public function registerAdmin(Request $request): JsonResponse
    {
        if (! $this->adminRegistrationAvailable()) {
            return response()->json([
                'message' => 'Publiska administratora reģistrācija vairs nav pieejama.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'surname' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', Rule::unique('employees', 'email')],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $employee = Employee::create([
            ...$validated,
            'role' => 'administrators',
        ]);

        return response()->json([
            'message' => 'Administratora profils izveidots.',
            'data' => $this->createSessionPayload($employee, 'employee'),
        ], 201);
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

    private function adminRegistrationAvailable(): bool
    {
        return ! Employee::query()->exists() && ! Client::query()->exists();
    }

    private function createSessionPayload(Client|Employee $actor, string $actorType): array
    {
        $plainToken = bin2hex(random_bytes(32));

        ApiToken::create([
            'actor_type' => $actorType,
            'actor_id' => $actor->id,
            'token' => hash('sha256', $plainToken),
        ]);

        return [
            'token' => $plainToken,
            'actorType' => $actorType,
            'user' => $this->formatActor($actor, $actorType),
        ];
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