<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cylinder;
use App\Models\Status;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::query()->with([
            'cylinder:id,serial_number,status_id',
            'cylinder.status:id,name',
            'client:id,name,surname,username',
            'employee:id,name,surname,role',
        ]);

        if ($this->currentActorType($request) === 'client') {
            $query->where('client_id', $this->currentActor($request)?->id);
        }

        if ($actionType = $request->string('action_type')->trim()->value()) {
            $query->where('action_type', $actionType);
        }

        return response()->json([
            'data' => $query->latest()->get(),
        ]);
    }

    public function issue(Request $request): JsonResponse
    {
        $employee = $this->currentActor($request);

        $data = $request->validate([
            'cylinder_id' => ['required', 'exists:cylinders,id'],
            'client_id' => ['required', 'exists:clients,id'],
            'issue_date' => ['nullable', 'date'],
        ]);

        $cylinder = Cylinder::query()->with('status')->findOrFail($data['cylinder_id']);

        if ($cylinder->status?->name === 'pie klienta') {
            return response()->json([
                'message' => 'Balons jau atrodas pie klienta.',
            ], 422);
        }

        $issuedStatus = Status::query()->where('name', 'pie klienta')->firstOrFail();

        // Issuing creates a new history entry instead of mutating earlier transactions.
        $transaction = Transaction::create([
            'cylinder_id' => $cylinder->id,
            'client_id' => $data['client_id'],
            'employee_id' => $employee->id,
            'issue_date' => $data['issue_date'] ?? Carbon::now()->toDateString(),
            'return_date' => null,
            'action_type' => 'izsniegts',
        ]);

        $cylinder->update(['status_id' => $issuedStatus->id]);

        return response()->json([
            'message' => 'Balons izsniegts klientam.',
            'data' => $transaction->load(['cylinder:id,serial_number', 'client:id,name,surname', 'employee:id,name,surname']),
        ], 201);
    }

    public function returnCylinder(Request $request): JsonResponse
    {
        $employee = $this->currentActor($request);

        $data = $request->validate([
            'cylinder_id' => ['required', 'exists:cylinders,id'],
            'status_id' => ['required', 'exists:statuses,id'],
            'return_date' => ['nullable', 'date'],
        ]);

        $cylinder = Cylinder::query()->with(['status', 'latestTransaction'])->findOrFail($data['cylinder_id']);
        $latestTransaction = $cylinder->latestTransaction;

        if (! $latestTransaction || $latestTransaction->action_type !== 'izsniegts') {
            return response()->json([
                'message' => 'Balonam nav aktīvs izsniegšanas ieraksts.',
            ], 422);
        }

        $returnStatus = Status::findOrFail($data['status_id']);

        if ($returnStatus->name === 'pie klienta') {
            return response()->json([
                'message' => 'Atgrieztam balonam nevar saglabāt statusu pie klienta.',
            ], 422);
        }

        // Returns are also stored as separate entries so issue and return actions remain auditable.
        $transaction = Transaction::create([
            'cylinder_id' => $cylinder->id,
            'client_id' => $latestTransaction->client_id,
            'employee_id' => $employee->id,
            'issue_date' => $latestTransaction->issue_date,
            'return_date' => $data['return_date'] ?? Carbon::now()->toDateString(),
            'action_type' => 'atgriezts',
        ]);

        $cylinder->update(['status_id' => $returnStatus->id]);

        return response()->json([
            'message' => 'Balons pieņemts atpakaļ.',
            'data' => $transaction->load(['cylinder:id,serial_number', 'client:id,name,surname', 'employee:id,name,surname']),
        ], 201);
    }
}