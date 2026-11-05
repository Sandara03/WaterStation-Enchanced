<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController, ProductController, OrderController, DashboardController, CustomerController, DeliveryController, ReportController, InventoryController, AuditLogController, ExpenseController};

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
Route::middleware('token.auth')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/password', [AuthController::class, 'password'])->middleware('throttle:5,1');
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::apiResource('customers', CustomerController::class)->except('destroy');
    Route::get('/deliveries', [DeliveryController::class, 'index']);
    Route::post('/deliveries', [DeliveryController::class, 'store']);
    Route::patch('/deliveries/{delivery}', [DeliveryController::class, 'update']);
    Route::get('/inventory/movements', [InventoryController::class, 'movements']);
    Route::middleware('admin')->group(function () {
        Route::apiResource('products', ProductController::class)->only(['store', 'update', 'destroy']);
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
        Route::post('/orders/{order}/void', [OrderController::class, 'void']);
        Route::post('/inventory/{product}/adjust', [InventoryController::class, 'adjust']);
        Route::get('/reports/summary', [ReportController::class, 'summary']);
        Route::get('/reports/export', [ReportController::class, 'export']);
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
        Route::get('/expenses', [ExpenseController::class, 'index']);
        Route::post('/expenses', [ExpenseController::class, 'store']);
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy']);
    });
});
