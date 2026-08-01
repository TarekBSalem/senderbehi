<?php

use App\Http\Controllers\Admin\AdminAnalyticsController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminMailLogController;
use App\Http\Controllers\Admin\AdminSmtpController;
use App\Http\Controllers\Admin\AdminSubscriptionPlanController;
use App\Http\Controllers\Admin\AdminSuppressionController;
use App\Http\Controllers\Admin\AdminTemplateController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BounceWebhookController;
use App\Http\Controllers\CampaignAnalyticsController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\DocController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

// Public marketing pages
Route::inertia('/', 'welcome')->name('home');
Route::get('pricing', [PageController::class, 'pricing'])->name('pricing');
Route::get('features', [PageController::class, 'features'])->name('features');
Route::get('contact', [PageController::class, 'contact'])->name('contact');
Route::post('contact', [PageController::class, 'sendContact'])->name('contact.send');
Route::inertia('privacy', 'public/privacy')->name('privacy');
Route::inertia('terms', 'public/terms')->name('terms');
Route::get('docs', [DocController::class, 'index'])->name('docs.index');
Route::get('docs/{doc}', [DocController::class, 'show'])->name('docs.show');
Route::get('blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('blog/{blogPost}', [BlogController::class, 'show'])->name('blog.show');
Route::get('changelog', [ChangelogController::class, 'index'])->name('changelog');
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Webhooks (no auth — called by SMTP providers)
Route::post('webhooks/bounce', BounceWebhookController::class)->name('webhooks.bounce');

// Tracking routes (no auth — accessed from email clients)
Route::get('track/open/{campaign}/{recipient}', [TrackingController::class, 'trackOpen'])->name('track.open');
Route::get('track/click/{campaign}/{recipient}', [TrackingController::class, 'trackClick'])->name('track.click');
Route::get('unsubscribe/{campaign}/{recipient}', [TrackingController::class, 'unsubscribe'])->name('unsubscribe');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Templates
    Route::get('templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::get('templates/{slug}', [TemplateController::class, 'show'])->name('templates.show');

    // Campaigns
    Route::resource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/send', [CampaignController::class, 'send'])->name('campaigns.send');

    // Campaign Analytics
    Route::get('campaigns/{campaign}/analytics', [CampaignAnalyticsController::class, 'index'])->name('campaigns.analytics');
    Route::get('campaigns/{campaign}/analytics/export', [CampaignAnalyticsController::class, 'export'])->name('campaigns.analytics.export');

    // Recipients (nested under campaigns)
    Route::get('campaigns/{campaign}/recipients', [RecipientController::class, 'index'])->name('recipients.index');
    Route::post('campaigns/{campaign}/recipients', [RecipientController::class, 'store'])->name('recipients.store');
    Route::post('campaigns/{campaign}/recipients/import', [RecipientController::class, 'importCsv'])->name('recipients.import');
    Route::post('campaigns/{campaign}/recipients/resend', [RecipientController::class, 'resend'])->name('recipients.resend');
    Route::delete('campaigns/{campaign}/recipients/{recipient}', [RecipientController::class, 'destroy'])->name('recipients.destroy');

    // Admin Panel
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('analytics', [AdminAnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('mail-logs', [AdminMailLogController::class, 'index'])->name('mail-logs.index');
        Route::delete('mail-logs', [AdminMailLogController::class, 'clear'])->name('mail-logs.clear');
        Route::delete('mail-logs/{mailLog}', [AdminMailLogController::class, 'destroy'])->name('mail-logs.destroy');

        Route::get('suppressions', [AdminSuppressionController::class, 'index'])->name('suppressions.index');
        Route::post('suppressions', [AdminSuppressionController::class, 'store'])->name('suppressions.store');
        Route::delete('suppressions', [AdminSuppressionController::class, 'clear'])->name('suppressions.clear');
        Route::delete('suppressions/{suppression}', [AdminSuppressionController::class, 'destroy'])->name('suppressions.destroy');
        Route::get('smtp', [AdminSmtpController::class, 'index'])->name('smtp.index');
        Route::put('smtp', [AdminSmtpController::class, 'update'])->name('smtp.update');
        Route::post('smtp/test', [AdminSmtpController::class, 'test'])->name('smtp.test');

        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('users/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('users/{user}', [AdminUserController::class, 'show'])->name('users.show');
        Route::put('users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
        Route::put('users/{user}/plan', [AdminUserController::class, 'updatePlan'])->name('users.plan');
        Route::post('users/{user}/block', [AdminUserController::class, 'block'])->name('users.block');
        Route::post('users/{user}/unblock', [AdminUserController::class, 'unblock'])->name('users.unblock');

        Route::get('subscription-plans', [AdminSubscriptionPlanController::class, 'index'])->name('subscription-plans.index');
        Route::get('subscription-plans/create', [AdminSubscriptionPlanController::class, 'create'])->name('subscription-plans.create');
        Route::post('subscription-plans', [AdminSubscriptionPlanController::class, 'store'])->name('subscription-plans.store');
        Route::get('subscription-plans/{plan}/edit', [AdminSubscriptionPlanController::class, 'edit'])->name('subscription-plans.edit');
        Route::put('subscription-plans/{plan}', [AdminSubscriptionPlanController::class, 'update'])->name('subscription-plans.update');
        Route::delete('subscription-plans/{plan}', [AdminSubscriptionPlanController::class, 'destroy'])->name('subscription-plans.destroy');

        Route::get('templates', [AdminTemplateController::class, 'index'])->name('templates.index');
        Route::get('templates/create', [AdminTemplateController::class, 'create'])->name('templates.create');
        Route::post('templates', [AdminTemplateController::class, 'store'])->name('templates.store');
        Route::get('templates/{template}/edit', [AdminTemplateController::class, 'edit'])->name('templates.edit');
        Route::put('templates/{template}', [AdminTemplateController::class, 'update'])->name('templates.update');
        Route::delete('templates/{template}', [AdminTemplateController::class, 'destroy'])->name('templates.destroy');

        Route::get('categories', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
        Route::post('categories', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::get('categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');
    });
});

require __DIR__.'/settings.php';
