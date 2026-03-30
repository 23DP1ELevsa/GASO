<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Cylinder;
use App\Models\Report;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Report::query()
                ->with('employee:id,name,surname,email')
                ->latest('created_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function show(Report $report): JsonResponse
    {
        $report->load('employee:id,name,surname,email');

        return response()->json([
            'data' => [
                'report' => $report,
                'payload' => $report->payload ?? $this->buildPayload($report->type),
            ],
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['balonu atskaite', 'klientu atskaite', 'darijumu atskaite'])],
        ]);

        $payload = $this->buildPayload($data['type']);

        $report = Report::create([
            'employee_id' => $this->currentActor($request)?->id,
            'payload' => $payload,
            'type' => $data['type'],
        ]);

        return response()->json([
            'message' => 'Atskaite izģenerēta.',
            'data' => [
                'report' => $report->load('employee:id,name,surname,email'),
                'payload' => $payload,
            ],
        ]);
    }

    private function buildPayload(string $type): array
    {
        return match ($type) {
            'balonu atskaite' => [
                'totals' => [
                    'count' => Cylinder::count(),
                    'inspectionDue' => Cylinder::whereDate('inspection_date', '<=', now()->addDays(30)->toDateString())->count(),
                ],
                'items' => Cylinder::query()->with('status:id,name')->orderBy('serial_number')->get()->all(),
            ],
            'klientu atskaite' => [
                'totals' => [
                    'count' => Client::count(),
                ],
                'items' => Client::query()
                    ->withCount('transactions')
                    ->orderBy('surname')
                    ->orderBy('name')
                    ->get()
                    ->all(),
            ],
            default => [
                'totals' => [
                    'count' => Transaction::count(),
                    'issued' => Transaction::where('action_type', 'izsniegts')->count(),
                    'returned' => Transaction::where('action_type', 'atgriezts')->count(),
                ],
                'items' => Transaction::query()
                    ->with(['cylinder:id,serial_number', 'client:id,name,surname', 'employee:id,name,surname'])
                    ->latest()
                    ->get()
                    ->all(),
            ],
        };
    }
}