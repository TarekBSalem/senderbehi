<?php

use App\Http\Controllers\Admin\AdminSmtpController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Templates
    Route::get('templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::get('templates/{slug}', [TemplateController::class, 'show'])->name('templates.show');

    // Campaigns
    Route::resource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/send', [CampaignController::class, 'send'])->name('campaigns.send');

    // Recipients (nested under campaigns)
    Route::get('campaigns/{campaign}/recipients', [RecipientController::class, 'index'])->name('recipients.index');
    Route::post('campaigns/{campaign}/recipients', [RecipientController::class, 'store'])->name('recipients.store');
    Route::post('campaigns/{campaign}/recipients/import', [RecipientController::class, 'importCsv'])->name('recipients.import');
    Route::delete('campaigns/{campaign}/recipients/{recipient}', [RecipientController::class, 'destroy'])->name('recipients.destroy');

    // Admin SMTP Settings
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('smtp', [AdminSmtpController::class, 'index'])->name('smtp.index');
        Route::put('smtp', [AdminSmtpController::class, 'update'])->name('smtp.update');
        Route::post('smtp/test', [AdminSmtpController::class, 'test'])->name('smtp.test');
    });
});

require __DIR__.'/settings.php';
