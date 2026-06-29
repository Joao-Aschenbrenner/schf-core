<?php

use App\Http\Controllers\Api\ApiDocsController;
use App\Http\Controllers\Api\LicenseController;
use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\ConciliationController;
use App\Http\Controllers\CronogramaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DdaController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\HealthPlanController;
use App\Http\Controllers\NfeController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PayableController;
use App\Http\Controllers\PreLaunchController;
use App\Http\Controllers\RestoreController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Admin\AuditAdminController;
use App\Http\Controllers\Admin\BackupAdminController;
use App\Http\Controllers\Admin\IntegrityController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\MigrationBundleController;
use App\Http\Controllers\SetupWizardController;
use App\Http\Controllers\UpdateController;
use App\Models\Historico\HistoricoFornecedor;
use App\Models\Historico\HistoricoNota;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'system' => 'SCHF',
        'version' => config('app.version', '1.2.0'),
    ]);
});

Route::get('/docs', ApiDocsController::class)->name('api.docs');

Route::post('/auth/login', [AuthController::class, 'login']);

// Setup Wizard (public, no auth)
Route::prefix('setup')->group(function () {
    Route::get('/status', [SetupWizardController::class, 'status']);
    Route::post('/organization', [SetupWizardController::class, 'createOrganization']);
    Route::post('/admin', [SetupWizardController::class, 'createAdmin']);
    Route::post('/complete', [SetupWizardController::class, 'complete']);
});

// Master Admin Authentication (separate from operational auth)
Route::prefix('admin')->group(function () {
    Route::post('/auth/master-login', [AdminController::class, 'masterLogin']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/master-logout', [AdminController::class, 'masterLogout']);

        // Admin Panel - requires auth:sanctum (controller handles access-admin gate)
        Route::middleware('auth:sanctum')->group(function () {
            // Dashboard & System
            Route::get('/dashboard', [AdminController::class, 'dashboard']);
            Route::get('/system/health', [AdminController::class, 'systemHealth']);
            Route::post('/system/cache', [AdminController::class, 'clearCache']);
            Route::get('/system/logs', [AdminController::class, 'getLogs']);
            Route::get('/system/info', [MaintenanceController::class, 'systemInfo']);

            // Containers
            Route::get('/containers', [AdminController::class, 'getContainers']);
            Route::post('/containers/{name}/restart', [AdminController::class, 'restartContainer']);
            Route::get('/containers/{name}/logs', [AdminController::class, 'getContainerLogs']);

            // Queue
            Route::post('/queue/restart', [AdminController::class, 'restartQueue']);

            // Updates
            Route::get('/updates/check', [UpdateController::class, 'check']);
            Route::get('/updates/check/{version}', [UpdateController::class, 'checkVersion']);
            Route::get('/updates/versions', [UpdateController::class, 'versions']);
            Route::get('/updates/changelog', [UpdateController::class, 'changelog']);
            Route::get('/updates/history', [UpdateController::class, 'history']);
            Route::post('/updates/download/{version}', [UpdateController::class, 'download']);
            Route::post('/updates/verify/{version}', [UpdateController::class, 'verify']);
            Route::post('/updates/run', [UpdateController::class, 'run']);
            Route::post('/updates/rollback', [UpdateController::class, 'rollback']);

            // User Management
            Route::get('/users', [UserAdminController::class, 'index']);
            Route::post('/users', [UserAdminController::class, 'store']);
            Route::get('/users/{id}', [UserAdminController::class, 'show']);
            Route::put('/users/{id}', [UserAdminController::class, 'update']);
            Route::delete('/users/{id}', [UserAdminController::class, 'destroy']);
            Route::post('/users/{id}/reset-password', [UserAdminController::class, 'resetPassword']);
            Route::post('/users/{id}/toggle-master', [UserAdminController::class, 'toggleMaster']);
            Route::post('/users/{id}/assign-role', [UserAdminController::class, 'assignRole']);
            Route::post('/users/{id}/remove-role', [UserAdminController::class, 'removeRole']);

            // Permissions & Roles
            Route::get('/permissions', [UserAdminController::class, 'getPermissions']);
            Route::get('/roles', [UserAdminController::class, 'getRoles']);

            // Audit
            Route::get('/audit', [AuditAdminController::class, 'index']);
            Route::get('/audit/export/{format}', [AuditAdminController::class, 'export']);

            // Backups
            Route::get('/backups', [BackupAdminController::class, 'index']);
            Route::post('/backups', [BackupAdminController::class, 'manualBackup']);
            Route::get('/backups/{id}', [BackupAdminController::class, 'show']);
            Route::get('/backups/{id}/download', [BackupAdminController::class, 'download']);
            Route::post('/backups/{id}/restore', [BackupAdminController::class, 'restore']);
            Route::delete('/backups/{id}', [BackupAdminController::class, 'destroy']);

            // Integrity
            Route::get('/integrity', [IntegrityController::class, 'index']);
            Route::post('/integrity/check', [IntegrityController::class, 'checkAll']);
            Route::post('/integrity/run-tests', [IntegrityController::class, 'runTests']);

            // Maintenance
            Route::post('/maintenance/clear', [MaintenanceController::class, 'clearCache']);
            Route::post('/maintenance/queue/restart', [MaintenanceController::class, 'restartQueue']);
            Route::post('/maintenance/sessions', [MaintenanceController::class, 'clearSessions']);
            Route::post('/maintenance/logs', [MaintenanceController::class, 'clearLogs']);

            // Migration Bundle Import
            Route::prefix('migration/bundles')->group(function () {
                Route::post('/validate', [MigrationBundleController::class, 'validateBundle']);
                Route::post('/preview', [MigrationBundleController::class, 'preview']);
                Route::post('/import', [MigrationBundleController::class, 'import']);
            });
        });
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/auth/password', [AuthController::class, 'changePassword']);
    Route::post('/auth/revoke-tokens', [AuthController::class, 'revokeTokens']);

    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('/dashboard/operational', [DashboardController::class, 'operational']);

    Route::apiResource('suppliers', SupplierController::class);
    Route::get('suppliers/{supplier}/financial-summary', [SupplierController::class, 'financialSummary']);

    Route::apiResource('organizations', OrganizationController::class);
    Route::post('organizations/{organization}/deactivate', [OrganizationController::class, 'deactivate']);
    Route::post('organizations/{organization}/activate', [OrganizationController::class, 'activate']);
    Route::post('organizations/{organization}/set-primary', [OrganizationController::class, 'setPrimary']);

    Route::apiResource('health-plans', HealthPlanController::class);
    Route::post('health-plans/{healthPlan}/resource-plans', [HealthPlanController::class, 'addResourcePlan']);
    Route::get('health-plans/{healthPlan}/balance', [HealthPlanController::class, 'balance']);

    Route::apiResource('expense-categories', ExpenseCategoryController::class);

    Route::apiResource('bank-accounts', BankAccountController::class);

    Route::apiResource('nfes', NfeController::class);
    Route::post('nfes/{nfe}/confirm', [NfeController::class, 'confirm']);
    Route::post('nfes/{nfe}/generate-payable', [NfeController::class, 'generatePayable']);

    Route::apiResource('payables', PayableController::class);
    Route::post('payables/{payable}/approve', [PayableController::class, 'approve']);
    Route::post('payables/{payable}/pay', [PayableController::class, 'pay']);
    Route::get('payables/aging-report', [PayableController::class, 'agingReport']);

    Route::apiResource('receivables', \App\Http\Controllers\Operacional\ReceivableController::class);
    Route::post('receivables/{receivable}/approve', [\App\Http\Controllers\Operacional\ReceivableController::class, 'approve']);
    Route::post('receivables/{receivable}/receive', [\App\Http\Controllers\Operacional\ReceivableController::class, 'receive']);

    Route::apiResource('provisions', \App\Http\Controllers\Operacional\ProvisionController::class);
    Route::post('provisions/{provision}/confirm', [\App\Http\Controllers\Operacional\ProvisionController::class, 'confirm']);
    Route::post('provisions/{provision}/pay', [\App\Http\Controllers\Operacional\ProvisionController::class, 'pay']);
    Route::post('provisions/{provision}/cancel', [\App\Http\Controllers\Operacional\ProvisionController::class, 'cancel']);

    Route::apiResource('ddas', DdaController::class)->except(['update', 'destroy']);
    Route::post('ddas/{dda}/link-payable', [DdaController::class, 'linkToPayable']);
    Route::post('ddas/{dda}/reject', [DdaController::class, 'reject']);
    Route::post('ddas/bulk-import', [DdaController::class, 'bulkImport']);

    Route::apiResource('pre-launches', PreLaunchController::class)->except(['destroy']);
    Route::post('pre-launches/{preLaunch}/confirm', [PreLaunchController::class, 'confirm']);
    Route::post('pre-launches/{preLaunch}/cancel', [PreLaunchController::class, 'cancel']);

    Route::get('conciliation/statements', [ConciliationController::class, 'indexStatements']);
    Route::get('conciliation/statements/{statement}', [ConciliationController::class, 'showStatement']);
    Route::post('conciliation/import-ofx', [ConciliationController::class, 'importOfx']);
    Route::post('conciliation/conciliate', [ConciliationController::class, 'conciliate']);
    Route::post('conciliation/unmatch/{item}', [ConciliationController::class, 'unmatch']);
    Route::post('conciliation/auto-match/{statement}', [ConciliationController::class, 'autoMatch']);

    Route::get('/cronograma', [CronogramaController::class, 'index']);

    Route::get('/reports/suppliers', [ReportController::class, 'supplierReport']);
    Route::get('/reports/categories', [ReportController::class, 'categoryReport']);
    Route::get('/reports/plans', [ReportController::class, 'planReport']);
    Route::get('/reports/cash-flow', [ReportController::class, 'cashFlow']);
    Route::get('/reports/prestacao-contas', [ReportController::class, 'prestacaoContas']);

    Route::get('audit-trail', [AuditTrailController::class, 'index']);
    Route::get('audit-trail/{modelType}/{modelId}', [AuditTrailController::class, 'modelTimeline']);

    // Operacional 2026+
    Route::prefix('operacional')->group(function () {
        Route::apiResource('receivables', \App\Http\Controllers\Operacional\ReceivableController::class);
        Route::post('receivables/{receivable}/approve', [\App\Http\Controllers\Operacional\ReceivableController::class, 'approve']);
        Route::post('receivables/{receivable}/receive', [\App\Http\Controllers\Operacional\ReceivableController::class, 'receive']);

        Route::apiResource('provisions', \App\Http\Controllers\Operacional\ProvisionController::class);
        Route::post('provisions/{provision}/confirm', [\App\Http\Controllers\Operacional\ProvisionController::class, 'confirm']);
        Route::post('provisions/{provision}/pay', [\App\Http\Controllers\Operacional\ProvisionController::class, 'pay']);
        Route::post('provisions/{provision}/cancel', [\App\Http\Controllers\Operacional\ProvisionController::class, 'cancel']);

        Route::apiResource('cash-registers', \App\Http\Controllers\Operacional\CashRegisterController::class)->except(['update', 'destroy']);
        Route::put('cash-registers/{cashRegister}/close', [\App\Http\Controllers\Operacional\CashRegisterController::class, 'close']);

        Route::apiResource('cash-movements', \App\Http\Controllers\Operacional\CashMovementController::class)->only(['index', 'store', 'show']);

        Route::apiResource('bank-investments', \App\Http\Controllers\Operacional\BankInvestmentController::class);
        Route::post('bank-investments/{bankInvestment}/redeem', [\App\Http\Controllers\Operacional\BankInvestmentController::class, 'redeem']);

        Route::get('bank-operations/extrato', [\App\Http\Controllers\Operacional\BankOperationController::class, 'extrato']);
        Route::apiResource('bank-operations', \App\Http\Controllers\Operacional\BankOperationController::class)->only(['index', 'store', 'show']);

        Route::apiResource('export-jobs', \App\Http\Controllers\Operacional\ExportJobController::class)->except(['update', 'destroy']);
        Route::get('export-jobs/{exportJob}/download', [\App\Http\Controllers\Operacional\ExportJobController::class, 'download']);
    });

    // Export
    Route::post('export/csv', [\App\Http\Controllers\Operacional\ExportJobController::class, 'exportCsv']);
    Route::post('export/xlsx', [\App\Http\Controllers\Operacional\ExportJobController::class, 'exportXlsx']);

    // Backup & Restore
    Route::prefix('backups')->group(function () {
        Route::get('/', [BackupController::class, 'index']);
        Route::post('/', [BackupController::class, 'store']);
        Route::post('cleanup', [BackupController::class, 'cleanup']);
        Route::get('{backup}/verify', [BackupController::class, 'verify']);
        Route::get('{backup}/download', [BackupController::class, 'download']);
        Route::post('{backup}/restore', [RestoreController::class, 'restore']);
        Route::get('{backup}/validate', [RestoreController::class, 'validate']);
        Route::get('{backup}', [BackupController::class, 'show']);
        Route::delete('{backup}', [BackupController::class, 'destroy']);
    });

    Route::prefix('historico')->group(function () {
        Route::get('fornecedores', fn () => response()->json(HistoricoFornecedor::query()->paginate()));
        Route::get('notas', fn () => response()->json(HistoricoNota::query()->paginate()));
        Route::get('notas/{nota}', fn (HistoricoNota $nota) => response()->json(['data' => $nota]));
    });

    // License Management
    Route::prefix('license')->group(function () {
        Route::get('/', [LicenseController::class, 'index']);
        Route::get('/info', [LicenseController::class, 'info']);
        Route::post('/activate', [LicenseController::class, 'activate']);
        Route::post('/validate', [LicenseController::class, 'validate']);
        Route::post('/trial', [LicenseController::class, 'createTrial']);
        Route::post('/{id}/suspend', [LicenseController::class, 'suspend']);
        Route::post('/{id}/revoke', [LicenseController::class, 'revoke']);
    });
});
