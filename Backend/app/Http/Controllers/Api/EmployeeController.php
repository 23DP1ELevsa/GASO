<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Employee::query()->orderBy('surname')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $employee = Employee::create($request->validate([
            'name' => ['required', 'string', 'max:50'],
            'surname' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', Rule::unique('employees', 'email')],
            'password' => ['required', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(['administrators', 'darbinieks'])],
        ]));

        return response()->json([
            'message' => 'Darbinieks pievienots.',
            'data' => $employee,
        ], 201);
    }

    public function destroy(Request $request, Employee $employee): JsonResponse
    {
        $currentEmployee = $this->currentActor($request);

        if ($currentEmployee?->id === $employee->id) {
            return response()->json([
                'message' => 'Aktivo administratoru dzest nevar.',
            ], 422);
        }

        if ($employee->transactions()->exists() || $employee->reports()->exists()) {
            return response()->json([
                'message' => 'Darbinieku nevar dzest, jo tam ir registri vai atskaites.',
            ], 422);
        }

        $employee->delete();

        return response()->json(['message' => 'Darbinieks dzests.']);
    }
}