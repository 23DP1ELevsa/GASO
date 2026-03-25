<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    protected function rules(?Client $client = null): array
    {
        $passwordRules = $client
            ? ['nullable', 'string', 'min:6']
            : ['required', 'string', 'min:6'];

        return [
            'name' => ['required', 'string', 'max:50'],
            'surname' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', Rule::unique('clients', 'email')->ignore($client?->id)],
            'password' => $passwordRules,
            'phone' => ['nullable', 'string', 'max:20'],
            'street' => ['required', 'string', 'max:100'],
            'home_number' => ['required', 'integer', 'min:1'],
            'flat_number' => ['nullable', 'integer', 'min:1'],
            'city' => ['required', 'string', 'max:50'],
            'zip_code' => ['required', 'string', 'max:20'],
            'username' => ['required', 'string', 'max:50', Rule::unique('clients', 'username')->ignore($client?->id)],
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $query = Client::query();

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'data' => $query->orderBy('surname')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $client = Client::create($request->validate($this->rules()));

        return response()->json([
            'message' => 'Klients pievienots.',
            'data' => $client,
        ], 201);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate($this->rules($client));

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $client->update($validated);

        return response()->json([
            'message' => 'Klienta profils atjaunots.',
            'data' => $client->fresh(),
        ]);
    }

    public function destroy(Client $client): JsonResponse
    {
        if ($client->transactions()->exists()) {
            return response()->json([
                'message' => 'Klientu nevar dzēst, jo tam ir darījumu vēsture.',
            ], 422);
        }

        $client->delete();

        return response()->json(['message' => 'Klients dzēsts.']);
    }
}