<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\IndustryController;
use App\Http\Controllers\MetrologyController;
use App\Http\Controllers\SecretariatController;
use App\Http\Controllers\MarketDataController;
use App\Http\Controllers\MarketAdminController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\PriceReportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt')->middleware('throttle:5,1');
});
Route::middleware('auth')->group(function () {
    Route::get('/panduan', [GuideController::class, 'index'])->name('guide.index');
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('permission:prices.view')->group(function () {
        Route::get('/harga', [PriceReportController::class, 'index'])->name('prices.index');
        Route::get('/api/harga/latest', [ApiController::class, 'latestPrice']);
        Route::get('/api/harga/trend', [ApiController::class, 'priceTrend']);
        Route::get('/api/harga/compare', [ApiController::class, 'compare']);
    });
    Route::middleware('permission:prices.create')->group(function () {
        Route::get('/harga/input', [PriceReportController::class, 'create'])->name('prices.create');
        Route::post('/harga', [PriceReportController::class, 'store'])->name('prices.store');
    });
    Route::get('/harga/{report}', [PriceReportController::class, 'show'])->middleware('permission:prices.view')->name('prices.show');
    Route::post('/harga/{report}/verifikasi', [PriceReportController::class, 'verify'])->middleware('permission:prices.verify')->name('prices.verify');

    Route::middleware('permission:stocks.view')->group(function () {
        Route::get('/stok', [StockReportController::class, 'index'])->name('stocks.index');
        Route::get('/api/stok/latest', [ApiController::class, 'latestStock']);
        Route::get('/api/stok/trend', [ApiController::class, 'stockTrend']);
    });
    Route::middleware('permission:stocks.create')->group(function () {
        Route::get('/stok/input', [StockReportController::class, 'create'])->name('stocks.create');
        Route::post('/stok', [StockReportController::class, 'store'])->name('stocks.store');
    });
    Route::get('/stok/{report}', [StockReportController::class, 'show'])->middleware('permission:stocks.view')->name('stocks.show');
    Route::post('/stok/{report}/verifikasi', [StockReportController::class, 'verify'])->middleware('permission:stocks.verify')->name('stocks.verify');

    Route::middleware('permission:master.manage')->group(function () {
        Route::get('/master', [MasterController::class, 'index'])->name('master.index');
        Route::post('/master/{type}', [MasterController::class, 'store'])->name('master.store');
        Route::patch('/master/{type}/{id}', [MasterController::class, 'update'])->name('master.update');
        Route::delete('/master/{type}/{id}', [MasterController::class, 'destroy'])->name('master.destroy');
        Route::patch('/master/{type}/{id}/toggle', [MasterController::class, 'toggle'])->name('master.toggle');
    });
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/laporan/pergerakan-harga', [ReportController::class, 'priceMovement'])->name('reports.price-movement');
        Route::get('/laporan/bulanan', [ReportController::class, 'monthly'])->name('reports.monthly');
        Route::get('/laporan/bulanan/csv', [ReportController::class, 'csv'])->name('reports.csv');
        Route::get('/laporan/stok', [ReportController::class, 'stocks'])->name('reports.stocks');
        Route::get('/laporan/stok/csv', [ReportController::class, 'stockCsv'])->name('reports.stocks.csv');
    });
    Route::middleware('permission:market_data.view')->group(function () {
        Route::get('/bidang-pasar', [MarketDataController::class, 'index'])->name('market-data.index');
        Route::get('/bidang-pasar/dashboard', [MarketAdminController::class, 'dashboard'])->name('market-data.dashboard');
    });
    Route::middleware('permission:market_data.create')->group(function () {
        Route::post('/bidang-pasar/pengelola', [MarketDataController::class, 'storeManager'])->name('market-data.managers.store');
        Route::put('/bidang-pasar/pengelola/{manager}', [MarketDataController::class, 'updateManager'])->name('market-data.managers.update');
        Route::post('/bidang-pasar/pedagang', [MarketDataController::class, 'storeMerchant'])->name('market-data.merchants.store');
        Route::put('/bidang-pasar/pedagang/{merchant}', [MarketDataController::class, 'updateMerchant'])->name('market-data.merchants.update');
        Route::delete('/bidang-pasar/{type}/{id}', [MarketDataController::class, 'destroy'])->name('market-data.destroy');
    });
    Route::post('/bidang-pasar/{type}/{id}/verifikasi', [MarketDataController::class, 'verify'])->middleware('permission:market_data.verify')->name('market-data.verify');
    Route::get('/bidang-pasar/export/excel', [MarketDataController::class, 'excel'])->middleware('permission:market_data.export')->name('market-data.excel');
    Route::get('/bidang-pasar/export/rekap', [MarketDataController::class, 'excel'])->middleware('permission:market_data.export')->name('market-data.csv');
    Route::middleware('permission:market_data.manage')->group(function () {
        Route::get('/bidang-pasar/master', [MarketAdminController::class, 'master'])->name('market-data.master');
        Route::post('/bidang-pasar/master/bidang-usaha', [MarketAdminController::class, 'storeBusinessType'])->name('market-data.business-types.store');
        Route::delete('/bidang-pasar/master/bidang-usaha/{id}', [MarketAdminController::class, 'deleteBusinessType'])->name('market-data.business-types.destroy');
        Route::get('/bidang-pasar/akun', [MarketAdminController::class, 'accounts'])->name('market-data.accounts');
        Route::post('/bidang-pasar/akun', [MarketAdminController::class, 'storeAccount'])->name('market-data.accounts.store');
        Route::put('/bidang-pasar/akun/{user}', [MarketAdminController::class, 'updateAccount'])->name('market-data.accounts.update');
        Route::patch('/bidang-pasar/akun/{user}/toggle', [MarketAdminController::class, 'toggleAccount'])->name('market-data.accounts.toggle');
    });
    Route::middleware('permission:industry.view')->group(function () {
        Route::get('/bidang-industri', [IndustryController::class, 'dashboard'])->name('industry.dashboard');
        Route::get('/bidang-industri/data', [IndustryController::class, 'index'])->name('industry.index');
    });
    Route::middleware('permission:industry.create')->group(function () {
        Route::post('/bidang-industri/data', [IndustryController::class, 'store'])->name('industry.store');
        Route::put('/bidang-industri/data/{industry}', [IndustryController::class, 'update'])->name('industry.update');
        Route::delete('/bidang-industri/data/{industry}', [IndustryController::class, 'destroy'])->name('industry.destroy');
    });
    Route::get('/bidang-industri/export/excel', [IndustryController::class, 'excel'])->middleware('permission:industry.export')->name('industry.excel');
    Route::middleware('permission:industry.manage')->group(function () {
        Route::get('/bidang-industri/master', [IndustryController::class, 'master'])->name('industry.master');
        Route::post('/bidang-industri/master/{type}', [IndustryController::class, 'storeMaster'])->name('industry.master.store');
        Route::put('/bidang-industri/master/{type}/{id}', [IndustryController::class, 'updateMaster'])->name('industry.master.update');
        Route::patch('/bidang-industri/master/{type}/{id}', [IndustryController::class, 'toggleMaster'])->name('industry.master.toggle');
        Route::get('/bidang-industri/akun', [IndustryController::class, 'accounts'])->name('industry.accounts');
        Route::post('/bidang-industri/akun', [IndustryController::class, 'storeAccount'])->name('industry.accounts.store');
        Route::put('/bidang-industri/akun/{user}', [IndustryController::class, 'updateAccount'])->name('industry.accounts.update');
        Route::patch('/bidang-industri/akun/{user}', [IndustryController::class, 'toggleAccount'])->name('industry.accounts.toggle');
    });
    Route::middleware('permission:metrology.view')->group(function () {
        Route::get('/bidang-metrologi', [MetrologyController::class, 'dashboard'])->name('metrology.dashboard');
        Route::get('/bidang-metrologi/data', [MetrologyController::class, 'index'])->name('metrology.index');
    });
    Route::middleware('permission:metrology.create')->group(function () {
        Route::post('/bidang-metrologi/data', [MetrologyController::class, 'store'])->name('metrology.store');
        Route::put('/bidang-metrologi/data/{record}', [MetrologyController::class, 'update'])->name('metrology.update');
        Route::delete('/bidang-metrologi/data/{record}', [MetrologyController::class, 'destroy'])->name('metrology.destroy');
    });
    Route::get('/bidang-metrologi/export/excel', [MetrologyController::class, 'excel'])->middleware('permission:metrology.export')->name('metrology.excel');
    Route::middleware('permission:metrology.manage')->group(function () {
        Route::get('/bidang-metrologi/master', [MetrologyController::class, 'master'])->name('metrology.master');
        Route::post('/bidang-metrologi/master/{type}', [MetrologyController::class, 'storeMaster'])->name('metrology.master.store');
        Route::put('/bidang-metrologi/master/{type}/{id}', [MetrologyController::class, 'updateMaster'])->name('metrology.master.update');
        Route::patch('/bidang-metrologi/master/{type}/{id}', [MetrologyController::class, 'toggleMaster'])->name('metrology.master.toggle');
        Route::get('/bidang-metrologi/akun', [MetrologyController::class, 'accounts'])->name('metrology.accounts');
        Route::post('/bidang-metrologi/akun', [MetrologyController::class, 'storeAccount'])->name('metrology.accounts.store');
        Route::patch('/bidang-metrologi/akun/{user}', [MetrologyController::class, 'toggleAccount'])->name('metrology.accounts.toggle');
    });
    Route::middleware('permission:secretariat.view')->group(function(){Route::get('/sekretariat',[SecretariatController::class,'dashboard'])->name('secretariat.dashboard');Route::get('/sekretariat/data',[SecretariatController::class,'index'])->name('secretariat.index');});
    Route::middleware('permission:secretariat.create')->group(function(){Route::post('/sekretariat/data',[SecretariatController::class,'store'])->name('secretariat.store');Route::put('/sekretariat/data/{record}',[SecretariatController::class,'update'])->name('secretariat.update');Route::delete('/sekretariat/data/{record}',[SecretariatController::class,'destroy'])->name('secretariat.destroy');});
    Route::get('/sekretariat/export/excel',[SecretariatController::class,'excel'])->middleware('permission:secretariat.export')->name('secretariat.excel');
    Route::middleware('permission:secretariat.manage')->group(function(){Route::get('/sekretariat/master',[SecretariatController::class,'master'])->name('secretariat.master');Route::post('/sekretariat/master/{type}',[SecretariatController::class,'storeMaster'])->name('secretariat.master.store');Route::put('/sekretariat/master/{type}/{id}',[SecretariatController::class,'updateMaster'])->name('secretariat.master.update');Route::patch('/sekretariat/master/{type}/{id}',[SecretariatController::class,'toggleMaster'])->name('secretariat.master.toggle');Route::get('/sekretariat/akun',[SecretariatController::class,'accounts'])->name('secretariat.accounts');Route::post('/sekretariat/akun',[SecretariatController::class,'storeAccount'])->name('secretariat.accounts.store');Route::patch('/sekretariat/akun/{user}',[SecretariatController::class,'toggleAccount'])->name('secretariat.accounts.toggle');});
    Route::middleware('permission:users.manage,roles.manage')->group(function () {
        Route::get('/administrator', [AdministratorController::class, 'index'])->name('administrator.index');
    });
    Route::middleware('permission:users.manage')->group(function () {
        Route::post('/administrator/accounts', [AdministratorController::class, 'storeAccount'])->name('administrator.accounts.store');
        Route::patch('/administrator/accounts/{user}', [AdministratorController::class, 'updateAccount'])->name('administrator.accounts.update');
        Route::patch('/administrator/accounts/{user}/toggle', [AdministratorController::class, 'toggleAccount'])->name('administrator.accounts.toggle');
        Route::delete('/administrator/accounts/{user}', [AdministratorController::class, 'destroyAccount'])->name('administrator.accounts.destroy');
    });
    Route::put('/administrator/roles/{role}/permissions', [AdministratorController::class, 'updatePermissions'])
        ->middleware('permission:roles.manage')->name('administrator.roles.permissions');

    Route::get('/api/dashboard', [DashboardController::class, 'api'])->middleware('permission:dashboard.view');
});
