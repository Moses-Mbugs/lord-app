<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


/*
|--------------------------------------------------------------------------
| Finance --- Moses was here
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Finance\BalancesPipelineController;
use App\Http\Controllers\Finance\BranchMoversController;
use App\Http\Controllers\Finance\CustomerTrendController;
use App\Http\Controllers\Finance\ExecSubSegmentController;
use App\Http\Controllers\Finance\FinanceHomeController;
use App\Http\Controllers\Finance\FinanceSegmentController;
use App\Http\Controllers\Finance\RelationshipManagerController;
use App\Http\Controllers\Finance\RmMoversController;
use App\Http\Controllers\Finance\RmWorkloadController;
use App\Http\Controllers\Finance\RmTargetController;
use App\Http\Controllers\Finance\RmTargetDashboardController;
use App\Http\Controllers\Finance\TopMoversController;
use App\Http\Controllers\Finance\LoansPipelineController;
use App\Http\Controllers\Finance\BranchDashboardController;
use App\Http\Controllers\Finance\customer_profitability\DashboardController;
use App\Http\Controllers\Finance\customer_profitability\UploadController;

Route::prefix('finance')->name('finance.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [FinanceHomeController::class, 'index'])->name('dashboard');
    Route::get('/float/live', [FinanceHomeController::class, 'floatLive'])->name('float.live');
    Route::get('/segment', [FinanceHomeController::class, 'segmentData'])->name('segment.data');

    Route::get('/segment/{segment}', [FinanceSegmentController::class, 'show'])->name('segment.show');
    Route::get('/segment/{segment}/cif-drivers', [FinanceSegmentController::class, 'cifDrivers'])->name('segment.cif-drivers');

    Route::get('/sub-segment-modal', [FinanceHomeController::class, 'subSegmentModal'])->name('sub-segment-modal');

    Route::prefix('balances')->name('balances.')->middleware('role:finance-admin')->group(function () {
        Route::get('pipeline', [BalancesPipelineController::class, 'index'])->name('pipeline');
        Route::post('pipeline', [BalancesPipelineController::class, 'run'])->name('pipeline.run');
    });

    Route::prefix('loans')->name('loans.')->middleware('role:finance-admin')->group(function () {
        Route::get('pipeline', [LoansPipelineController::class, 'index'])->name('pipeline');
        Route::post('pipeline/upload', [LoansPipelineController::class, 'upload'])->name('pipeline.upload');
        Route::post('pipeline/send', [LoansPipelineController::class, 'send'])->name('pipeline.send');
    });

    Route::prefix('top-movers')->name('top-movers.')->middleware('role:finance-admin')->group(function () {
        Route::get('/', [TopMoversController::class, 'index'])->name('index');
        Route::get('/data', [TopMoversController::class, 'data'])->name('data');
        Route::get('/loans-data', [TopMoversController::class, 'loansData'])->name('loans-data');
    });

    Route::prefix('sub-segment-movement')->name('sub-segment-movement.')->group(function () {
        Route::get('/', [FinanceHomeController::class, 'index'])->name('index');
        Route::post('/build', [FinanceHomeController::class, 'build'])->name('build');
        Route::get('/data', [FinanceHomeController::class, 'data'])->name('data');
        Route::get('/drilldown', [FinanceHomeController::class, 'drilldown'])->name('drilldown');
    });

    Route::prefix('exec-sub-segment')->name('exec-sub-segment.')->middleware('role:finance-admin')->group(function () {
        Route::get('/', [ExecSubSegmentController::class, 'index'])->name('index');
        Route::get('/data', [ExecSubSegmentController::class, 'data'])->name('data');
    });

    Route::prefix('branch-movers')->name('branch-movers.')->group(function () {
        Route::get('/', [BranchMoversController::class, 'index'])->name('index');
        Route::get('kpis', [BranchMoversController::class, 'kpis'])->name('kpis');
        Route::get('summary', [BranchMoversController::class, 'summary'])->name('summary');
        Route::get('top-movers', [BranchMoversController::class, 'topMovers'])->name('top-movers');
        Route::get('cif-movers', [BranchMoversController::class, 'cifMovers'])->name('cif-movers');
        Route::get('movement-chart', [BranchMoversController::class, 'movementChart'])->name('movement-chart');
        Route::get('branches', [BranchMoversController::class, 'branches'])->name('branches');
        Route::get('trend-summary', [BranchMoversController::class, 'trendSummary'])->name('trend-summary');
    });

    Route::prefix('relationship-managers')->name('relationship-managers.')->middleware('role:finance-admin')->group(function () {
        Route::get('/', [RelationshipManagerController::class, 'index'])->name('index');
        Route::get('data', [RelationshipManagerController::class, 'data'])->name('data');
        Route::get('segments', [RelationshipManagerController::class, 'segments'])->name('segments');
        Route::post('/', [RelationshipManagerController::class, 'store'])->name('store');
        Route::put('{relationshipManager}', [RelationshipManagerController::class, 'update'])->name('update');
        Route::delete('{relationshipManager}', [RelationshipManagerController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('rm-movers')->name('rm-movers.')->group(function () {
        Route::get('/', [RmMoversController::class, 'index'])->name('index');
        Route::post('build', [RmMoversController::class, 'build'])->name('build');
        Route::get('kpis', [RmMoversController::class, 'kpis'])->name('kpis');
        Route::get('data', [RmMoversController::class, 'data'])->name('data');
        Route::get('top-movers', [RmMoversController::class, 'topMovers'])->name('top-movers');
        Route::get('drilldown', [RmMoversController::class, 'drilldown'])->name('drilldown');
        Route::get('trend', [RmMoversController::class, 'trend'])->name('trend');
        Route::get('rm-list', [RmMoversController::class, 'rmList'])->name('rm-list');
        Route::get('segment-list', [RmMoversController::class, 'segmentList'])->name('segment-list');
        Route::get('subsegment-list', [RmMoversController::class, 'subsegmentList'])->name('subsegment-list');
        Route::get('single-rm-stats', [RmMoversController::class, 'singleRmStats'])->name('single-rm-stats');
    });

    Route::prefix('rm-workload')->name('rm-workload.')->group(function () {
        Route::get('/', [RmWorkloadController::class, 'index'])->name('index');
        Route::get('data', [RmWorkloadController::class, 'data'])->name('data');
        Route::get('accounts', [RmWorkloadController::class, 'accounts'])->name('accounts');
        Route::get('accounts/export', [RmWorkloadController::class, 'exportAccounts'])->name('accounts.export');
    });

    Route::prefix('rm-targets')->name('rm-targets.')->group(function () {
        Route::get('/', [RmTargetDashboardController::class, 'index'])->name('index');
        Route::get('rm-list', [RmTargetController::class, 'rmList'])->name('rm-list');

        Route::middleware('role:finance-admin')->group(function () {
            Route::get('manage', [RmTargetController::class, 'index'])->name('manage');
            Route::get('manage/data', [RmTargetController::class, 'data'])->name('manage.data');
            Route::post('manage', [RmTargetController::class, 'store'])->name('manage.store');
            Route::put('manage/{rmTarget}', [RmTargetController::class, 'update'])->name('manage.update');
            Route::delete('manage/{rmTarget}', [RmTargetController::class, 'destroy'])->name('manage.destroy');
        });
    });

    Route::prefix('customer-trend')->name('customer-trend.')->middleware('role:finance-admin')->group(function () {
        Route::get('/', [CustomerTrendController::class, 'index'])->name('index');
        Route::get('profile', [CustomerTrendController::class, 'profile'])->name('profile');
        Route::get('trend', [CustomerTrendController::class, 'trend'])->name('trend');
        Route::get('summary', [CustomerTrendController::class, 'summary'])->name('summary');
    });

    Route::prefix('branch-dashboard')->name('branch-dashboard.')->group(function () {
        Route::get('/', [BranchDashboardController::class, 'index'])->name('index');
    });

    // Customer Profitability module
    Route::prefix('customer-profitability')->name('customer_profitability.')->group(function () {
        Route::get('/', fn() => redirect()->route('finance.customer_profitability.upload'));

        Route::middleware('role:finance-admin')->group(function () {
            Route::get('upload', [UploadController::class, 'index'])->name('upload');
            Route::post('upload', [UploadController::class, 'store'])->name('upload.store');
        });

        Route::get('dashboard/{id}', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard/{id}/search', [DashboardController::class, 'searchCustomer'])->name('dashboard.search');
    });
});
