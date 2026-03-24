<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CylinderController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth.token')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/transactions', [TransactionController::class, 'index'])->middleware('role:employee,client');

    Route::middleware('role:employee')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/statuses', [StatusController::class, 'index']);

        Route::get('/cylinders', [CylinderController::class, 'index']);
        Route::get('/cylinders/{cylinder}', [CylinderController::class, 'show']);
        Route::patch('/cylinders/{cylinder}/status', [CylinderController::class, 'changeStatus']);

        Route::get('/clients', [ClientController::class, 'index']);
        Route::post('/transactions/issue', [TransactionController::class, 'issue']);
        Route::post('/transactions/return', [TransactionController::class, 'returnCylinder']);

        Route::get('/reports', [ReportController::class, 'index']);
        Route::get('/reports/generate', [ReportController::class, 'generate']);
    });

    Route::middleware('role:admin')->group(function () {
        Route::post('/cylinders', [CylinderController::class, 'store']);
        Route::put('/cylinders/{cylinder}', [CylinderController::class, 'update']);
        Route::delete('/cylinders/{cylinder}', [CylinderController::class, 'destroy']);

        Route::post('/clients', [ClientController::class, 'store']);
        Route::delete('/clients/{client}', [ClientController::class, 'destroy']);

        Route::get('/employees', [EmployeeController::class, 'index']);
        Route::post('/employees', [EmployeeController::class, 'store']);
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy']);
    });
});