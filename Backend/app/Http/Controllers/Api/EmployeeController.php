<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    protected function rules(?Employee $employee = null): array
    {
        $passwordRules = $employee
            ? ['nullable', 'string', 'min:6']
            : ['required', 'string', 'min:6'];

        return [
            'name' => ['required', 'string', 'max:50'],
            'surname' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', Rule::unique('employees', 'email')->ignore($employee?->id)],
            'password' => $passwordRules,
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(['administrators', 'darbinieks'])],
        ];
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Employee::query()->orderBy('surname')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $employee = Employee::create($request->validate($this->rules()));

        return response()->json([
            'message' => 'Darbinieks pievienots.',
            'data' => $employee,
        ], 201);
    }

    public function update(Request $request, Employee $employee): JsonResponse
    {
        $validated = $request->validate($this->rules($employee));

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $employee->update($validated);

        return response()->json([
            'message' => 'Darbinieka profils atjaunots.',
            'data' => $employee->fresh(),
        ]);
    }

    public function destroy(Request $request, Employee $employee): JsonResponse
    {
        $currentEmployee = $this->currentActor($request);

        // Prevent the active admin from deleting the account that is currently authorizing the session.
        if ($currentEmployee?->id === $employee->id) {
            return response()->json([
                'message' => 'Aktīvo administratoru dzēst nevar.',
            ], 422);
        }

        $employee->delete();

        return response()->json(['message' => 'Darbinieks dzēsts.']);
    }
}