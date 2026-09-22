<?php

use App\Http\Controllers\AffiliateClickController;
use App\Http\Controllers\AttemptResultController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterSubscriptionController;
use App\Http\Controllers\PublicContentController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SponsoredClickController;
use App\Http\Controllers\VerticalController;
use App\Livewire\Admin\AffiliateManager;
use App\Livewire\Admin\ApiManager;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\ImportManager;
use App\Livewire\Admin\LeadManager;
use App\Livewire\Admin\NewsletterManager;
use App\Livewire\Admin\QualityManager;
use App\Livewire\Admin\QuestionManager;
use App\Livewire\Admin\ReviewQueue;
use App\Livewire\Admin\SponsorshipManager;
use App\Livewire\Admin\TaxonomyManager;
use App\Livewire\Admin\TenantManager;
use App\Livewire\Admin\TestManager;
use App\Livewire\Admin\VerticalManager;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\TestRunner;
use App\Livewire\User\Dashboard as UserDashboard;
use App\Livewire\User\History;
use App\Livewire\User\Leaderboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemaps/verticals.xml', [SitemapController::class, 'verticals'])->name('sitemaps.verticals');
Route::get('/sitemaps/taxonomy.xml', [SitemapController::class, 'taxonomy'])->name('sitemaps.taxonomy');
Route::get('/sitemaps/tests.xml', [SitemapController::class, 'tests'])->name('sitemaps.tests');

Route::middleware('noindex')->group(function () {
    Route::get('/go/sponsor/{placement}', SponsoredClickController::class)
        ->whereNumber('placement')
        ->name('sponsor.click');
    Route::get('/go/resource/{resource}', AffiliateClickController::class)
        ->whereNumber('resource')
        ->name('affiliate.click');

    Route::get('/newsletter/confirm/{subscription}', [NewsletterSubscriptionController::class, 'confirm'])
        ->middleware('signed')
        ->whereNumber('subscription')
        ->name('newsletter.confirm');
    Route::get('/newsletter/unsubscribe/{subscription}', [NewsletterSubscriptionController::class, 'unsubscribe'])
        ->middleware('signed')
        ->whereNumber('subscription')
        ->name('newsletter.unsubscribe');

    Route::middleware('guest')->group(function () {
        Route::get('/login', Login::class)->name('login');
        Route::get('/register', Register::class)->name('register');

        Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
            ->name('auth.google.redirect');
        Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
            ->name('auth.google.callback');
    });

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    })->middleware('auth')->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', UserDashboard::class)->name('dashboard');
        Route::get('/history', History::class)->name('history');
        Route::get('/leaderboard', Leaderboard::class)->name('leaderboard');

        Route::get('/attempts/{attempt}/results', AttemptResultController::class)
            ->whereNumber('attempt')
            ->name('attempts.results');

        Route::get('/{vertical:slug}/{test:slug}/start', TestRunner::class)
            ->where(['vertical' => '[a-z0-9-]+', 'test' => '[a-z0-9-]+'])
            ->name('tests.start');
    });

    Route::prefix('admin')
        ->middleware(['auth', 'can:access-admin'])
        ->name('admin.')
        ->group(function () {
            Route::get('/', AdminDashboard::class)->name('dashboard');
            Route::get('/verticals', VerticalManager::class)->name('verticals');
            Route::get('/taxonomy', TaxonomyManager::class)->name('taxonomy');
            Route::get('/tests', TestManager::class)->name('tests');
            Route::get('/questions', QuestionManager::class)->name('questions');
            Route::get('/imports', ImportManager::class)->name('imports');
            Route::get('/review', ReviewQueue::class)->name('review');
            Route::get('/quality', QualityManager::class)->name('quality');
            Route::get('/monetization/sponsors', SponsorshipManager::class)->name('sponsors');
            Route::get('/monetization/leads', LeadManager::class)->name('leads');
            Route::get('/monetization/affiliate', AffiliateManager::class)->name('affiliate');
            Route::get('/monetization/newsletter', NewsletterManager::class)->name('newsletter');
            Route::get('/white-label', TenantManager::class)->name('tenants');
            Route::get('/api', ApiManager::class)->name('api');
        });
});

Route::get('/{vertical:slug}', [VerticalController::class, 'show'])
    ->where('vertical', '[a-z0-9-]+')
    ->name('verticals.show');

Route::get('/{vertical:slug}/{path}', [PublicContentController::class, 'show'])
    ->where([
        'vertical' => '[a-z0-9-]+',
        'path' => '[a-z0-9-]+(?:/[a-z0-9-]+)*',
    ])
    ->name('content.show');
