<?php

use App\Http\Controllers\Api\AppActionController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\CountryPresetController;
use App\Http\Controllers\Api\DiagnosticsController;
use App\Http\Controllers\Api\FirstScanController;
use App\Http\Controllers\Api\HiddenJobController;
use App\Http\Controllers\Api\JobStateController;
use App\Http\Controllers\Api\ListController;
use App\Http\Controllers\Api\RateController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\SourceController;
use App\Http\Controllers\Api\StampPreviewController;
use App\Http\Controllers\Api\StatusController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'app')->name('home');
Route::view('/settings', 'settings')->name('settings');
Route::view('/first-run', 'first-run')->name('first-run');

// The JSON API of the menu bar list, the settings window and the first run. Web middleware: session and CSRF.
Route::prefix('api')->name('api.')->group(function () {
    Route::get('list', ListController::class)->name('list');
    Route::put('jobs/{job}/state', JobStateController::class)->name('jobs.state')
        ->missing(fn () => response()->json(['message' => 'Job not found.'], 404));

    Route::get('hidden-jobs', [HiddenJobController::class, 'index'])->name('hidden-jobs.index');
    Route::delete('hidden-jobs/older', [HiddenJobController::class, 'destroyOlder'])->name('hidden-jobs.destroy-older');

    Route::get('settings', [SettingsController::class, 'show'])->name('settings.show');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::get('countries', CountryController::class)->name('countries');
    Route::get('country-preset', CountryPresetController::class)->name('country-preset');
    Route::get('stamp-preview', StampPreviewController::class)->name('stamp-preview');
    Route::get('sources', SourceController::class)->name('sources');

    Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::delete('companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy')
        ->missing(fn () => response()->json(['message' => 'Company not found.'], 404));

    Route::get('rates', RateController::class)->name('rates');
    Route::get('status', StatusController::class)->name('status');
    Route::get('diagnostics', DiagnosticsController::class)->name('diagnostics');

    Route::post('first-scan', [FirstScanController::class, 'store'])->name('first-scan.store');
    Route::get('first-scan', [FirstScanController::class, 'show'])->name('first-scan.show');

    Route::post('app/open-url', [AppActionController::class, 'openUrl'])->name('app.open-url');
    Route::post('app/settings', [AppActionController::class, 'openSettings'])->name('app.settings');
    Route::post('app/quit', [AppActionController::class, 'quit'])->name('app.quit');
    Route::post('app/open-list', [AppActionController::class, 'openList'])->name('app.open-list');
    Route::post('app/test-notification', [AppActionController::class, 'testNotification'])->name('app.test-notification');
    Route::post('app/show-data-folder', [AppActionController::class, 'showDataFolder'])->name('app.show-data-folder');
    Route::post('app/open-log-folder', [AppActionController::class, 'openLogFolder'])->name('app.open-log-folder');
    Route::post('app/copy-diagnostics', [AppActionController::class, 'copyDiagnostics'])->name('app.copy-diagnostics');
    Route::post('app/settings-title', [AppActionController::class, 'settingsTitle'])->name('app.settings-title');
});
