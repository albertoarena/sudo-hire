<?php

use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\DiagnosticsController;
use App\Http\Controllers\Api\JobStateController;
use App\Http\Controllers\Api\ListController;
use App\Http\Controllers\Api\RateController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\SourceController;
use App\Http\Controllers\Api\StatusController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'app')->name('home');

// The JSON API of the menu bar list and the settings window. Web middleware: session and CSRF.
Route::prefix('api')->name('api.')->group(function () {
    Route::get('list', ListController::class)->name('list');
    Route::put('jobs/{job}/state', JobStateController::class)->name('jobs.state')
        ->missing(fn () => response()->json(['message' => 'Job not found.'], 404));

    Route::get('settings', [SettingsController::class, 'show'])->name('settings.show');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::get('countries', CountryController::class)->name('countries');
    Route::get('sources', SourceController::class)->name('sources');

    Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::delete('companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy')
        ->missing(fn () => response()->json(['message' => 'Company not found.'], 404));

    Route::get('rates', RateController::class)->name('rates');
    Route::get('status', StatusController::class)->name('status');
    Route::get('diagnostics', DiagnosticsController::class)->name('diagnostics');
});
