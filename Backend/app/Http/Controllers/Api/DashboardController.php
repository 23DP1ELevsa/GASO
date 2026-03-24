<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Cylinder;
use App\Models\Employee;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $soon = Carbon::now()->addDays(30)->toDateString();

        return response()->json([
            'data' => [
                'metrics' => [
                    'totalCylinders' => Cylinder::count(),
                    'totalClients' => Client::count(),
                    'totalEmployees' => Employee::count(),
                    'activeIssued' => Cylinder::whereHas('status', fn ($query) => $query->where('name', 'pie klienta'))->count(),
                    'inspectionDueSoon' => Cylinder::whereDate('inspection_date', '<=', $soon)->count(),
                ],
                'statusBreakdown' => DB::table('cylinders')
                    ->join('statuses', 'statuses.id', '=', 'cylinders.status_id')
                    ->select('statuses.name', DB::raw('COUNT(cylinders.id) as total'))
                    ->groupBy('statuses.name')
                    ->orderBy('statuses.name')
                    ->get(),
                'recentTransactions' => Transaction::query()
                    ->with(['cylinder:id,serial_number', 'client:id,name,surname', 'employee:id,name,surname'])
                    ->latest()
                    ->limit(8)
                    ->get(),
            ],
        ]);
    }
}