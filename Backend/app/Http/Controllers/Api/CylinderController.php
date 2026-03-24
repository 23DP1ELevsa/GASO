<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cylinder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CylinderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Cylinder::query()->with([
            'status:id,name',
            'latestTransaction.client:id,name,surname',
            'latestTransaction.employee:id,name,surname',
        ]);

        if ($search = $request->string('search')->trim()->value()) {
            $query->where('serial_number', 'like', "%{$search}%");
        }

        if ($statusId = $request->integer('status_id')) {
            $query->where('status_id', $statusId);
        }

        return response()->json([
            'data' => $query->orderBy('serial_number')->get(),
        ]);
    }

    public function show(Cylinder $cylinder): JsonResponse
    {
        $cylinder->load([
            'status:id,name',
            'transactions' => fn ($query) => $query
                ->with(['client:id,name,surname', 'employee:id,name,surname'])
                ->latest(),
        ]);

        return response()->json(['data' => $cylinder]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateCylinder($request);

        $cylinder = Cylinder::create($data)->load('status:id,name');

        return response()->json([
            'message' => 'Balons pievienots.',
            'data' => $cylinder,
        ], 201);
    }

    public function update(Request $request, Cylinder $cylinder): JsonResponse
    {
        $data = $this->validateCylinder($request, $cylinder->id);

        $cylinder->update($data);

        return response()->json([
            'message' => 'Balona dati atjaunoti.',
            'data' => $cylinder->fresh('status:id,name'),
        ]);
    }

    public function destroy(Cylinder $cylinder): JsonResponse
    {
        if ($cylinder->transactions()->exists()) {
            return response()->json([
                'message' => 'Balonu nevar dzest, jo tam ir piesaistita darijumu vesture.',
            ], 422);
        }

        $cylinder->delete();

        return response()->json(['message' => 'Balons dzests.']);
    }

    public function changeStatus(Request $request, Cylinder $cylinder): JsonResponse
    {
        $data = $request->validate([
            'status_id' => ['required', 'exists:statuses,id'],
        ]);

        $cylinder->update($data);

        return response()->json([
            'message' => 'Balona statuss atjaunots.',
            'data' => $cylinder->fresh('status:id,name'),
        ]);
    }

    private function validateCylinder(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'status_id' => ['required', 'exists:statuses,id'],
            'serial_number' => ['required', 'string', 'max:50', Rule::unique('cylinders', 'serial_number')->ignore($ignoreId)],
            'capacity' => ['required', 'numeric', 'min:0.5'],
            'manufacture_date' => ['required', 'date'],
            'inspection_date' => ['required', 'date', 'after_or_equal:manufacture_date'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}