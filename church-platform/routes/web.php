<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Installer\InstallerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Installer Routes
Route::prefix('install')->group(function () {
    Route::get('/', [InstallerController::class, 'welcome'])->name('installer.welcome');
    Route::get('/database', [InstallerController::class, 'database'])->name('installer.database');
    Route::post('/database', [InstallerController::class, 'saveDatabase']);
    Route::get('/admin', [InstallerController::class, 'admin'])->name('installer.admin');
    Route::post('/admin', [InstallerController::class, 'saveAdmin']);
    Route::get('/church', [InstallerController::class, 'church'])->name('installer.church');
    Route::post('/church', [InstallerController::class, 'saveChurch']);
    Route::get('/finalize', [InstallerController::class, 'finalize'])->name('installer.finalize');
    Route::post('/finalize', [InstallerController::class, 'install']);
});

// Auth Routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login')->middleware('guest');

Route::post('/login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (\Illuminate\Support\Facades\Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        return redirect()->intended('/admin');
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
})->name('login.post');

Route::post('/logout', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// Social Auth Routes
Route::get('/auth/{provider}/redirect', [AuthController::class, 'socialRedirect'])->name('social.redirect');
Route::get('/auth/{provider}/callback', [AuthController::class, 'socialCallback'])->name('social.callback');

// Admin Routes
Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/manage/{section}', function ($section) {
        $validSections = [
            'verses', 'blessings', 'prayers', 'prayer-requests', 'events', 'posts', 'sermons',
            'books', 'bible-studies', 'reviews', 'testimonies', 'galleries', 'ministries',
            'contacts', 'newsletter', 'donations', 'users', 'settings',
            'announcements', 'pages', 'categories', 'menus', 'roles',
            'appearance', 'mobile-theme', 'translations',
            'homepage', 'system',
        ];
        if (!in_array($section, $validSections)) {
            abort(404);
        }
        return view('admin.manage', ['section' => $section]);
    })->name('admin.manage');
});

// Sitemap (SEO)
Route::get('/sitemap.xml', [\App\Http\Controllers\Api\SitemapController::class, 'index'])->name('sitemap');

// Frontend catch-all (SPA)
Route::get('/{any?}', function () {
    return view('welcome');
})->where('any', '.*')->name('home');
