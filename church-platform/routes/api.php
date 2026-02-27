<?php

use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\BibleStudyController;
use App\Http\Controllers\Api\BlessingController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\MinistryController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PrayerRequestController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SermonController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SitemapController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TestimonyController;
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\Api\VerseController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Auth Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public Content Routes
Route::get('/verses/today', [VerseController::class, 'today']);
Route::get('/blessings/today', [BlessingController::class, 'today']);
Route::get('/events/upcoming', [EventController::class, 'upcoming']);
Route::get('/posts/published', [PostController::class, 'published']);
Route::get('/posts/featured', [PostController::class, 'featured']);
Route::get('/posts/{slug}', [PostController::class, 'show']);
Route::get('/sermons/featured', [SermonController::class, 'featured']);
Route::get('/sermons/{slug}', [SermonController::class, 'show']);
Route::get('/books/featured', [BookController::class, 'featured']);
Route::get('/books/{book}', [BookController::class, 'show']);
Route::get('/books/{book}/download', [BookController::class, 'download']);
Route::get('/bible-studies/featured', [BibleStudyController::class, 'featured']);
Route::get('/bible-studies/{bibleStudy}', [BibleStudyController::class, 'show']);
Route::get('/reviews/approved', [ReviewController::class, 'approved']);
Route::get('/testimonies/approved', [TestimonyController::class, 'approved']);
Route::get('/testimonies/featured', [TestimonyController::class, 'featured']);
Route::get('/testimonies/{slug}', [TestimonyController::class, 'show']);
Route::get('/galleries', [GalleryController::class, 'index']);
Route::get('/galleries/{gallery}', [GalleryController::class, 'show']);
Route::get('/ministries', [MinistryController::class, 'index']);
Route::get('/ministries/{ministry}', [MinistryController::class, 'show']);
Route::get('/prayer-requests/public', [PrayerRequestController::class, 'publicRequests']);
Route::get('/settings', [SettingController::class, 'show']);
Route::get('/settings/widgets/public', [SettingController::class, 'widgetConfig']);

// Announcements (public)
Route::get('/announcements/active', [AnnouncementController::class, 'active']);

// Pages (public)
Route::get('/pages/published', [PageController::class, 'published']);
Route::get('/pages/{slug}', [PageController::class, 'show']);

// Categories (public)
Route::get('/categories', [CategoryController::class, 'all']);

// Menus (public)
Route::get('/menus/{location}', [MenuController::class, 'show']);

// Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index']);

// Public Submissions
Route::post('/contact', [ContactController::class, 'store']);
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe']);
Route::post('/prayer-requests', [PrayerRequestController::class, 'store']);
Route::post('/reviews', [ReviewController::class, 'store']);
Route::post('/testimonies', [TestimonyController::class, 'store']);
Route::post('/events/{event}/register', [EventController::class, 'register']);
Route::post('/prayer-requests/{prayerRequest}/pray', [PrayerRequestController::class, 'pray']);

// Authenticated Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Verses CRUD
    Route::get('/verses', [VerseController::class, 'index']);
    Route::post('/verses', [VerseController::class, 'store']);
    Route::put('/verses/{verse}', [VerseController::class, 'update']);
    Route::delete('/verses/{verse}', [VerseController::class, 'destroy']);
    Route::get('/verses/sample-csv', [VerseController::class, 'sampleCsv']);
    Route::post('/verses/import-csv', [VerseController::class, 'importCsv']);

    // Blessings CRUD
    Route::get('/blessings', [BlessingController::class, 'index']);
    Route::post('/blessings', [BlessingController::class, 'store']);
    Route::put('/blessings/{blessing}', [BlessingController::class, 'update']);
    Route::delete('/blessings/{blessing}', [BlessingController::class, 'destroy']);

    // Prayer Requests Admin
    Route::get('/prayer-requests', [PrayerRequestController::class, 'index']);
    Route::put('/prayer-requests/{prayerRequest}', [PrayerRequestController::class, 'update']);
    Route::patch('/prayer-requests/{prayerRequest}/status', [PrayerRequestController::class, 'updateStatus']);
    Route::delete('/prayer-requests/{prayerRequest}', [PrayerRequestController::class, 'destroy']);

    // Events CRUD
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{event}', [EventController::class, 'show']);
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{event}', [EventController::class, 'update']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);

    // Posts CRUD
    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::put('/posts/{post}', [PostController::class, 'update']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);

    // Sermons CRUD
    Route::get('/sermons', [SermonController::class, 'index']);
    Route::post('/sermons', [SermonController::class, 'store']);
    Route::put('/sermons/{sermon}', [SermonController::class, 'update']);
    Route::delete('/sermons/{sermon}', [SermonController::class, 'destroy']);

    // Books CRUD
    Route::get('/books', [BookController::class, 'index']);
    Route::post('/books', [BookController::class, 'store']);
    Route::put('/books/{book}', [BookController::class, 'update']);
    Route::delete('/books/{book}', [BookController::class, 'destroy']);

    // Bible Studies CRUD
    Route::get('/bible-studies', [BibleStudyController::class, 'index']);
    Route::post('/bible-studies', [BibleStudyController::class, 'store']);
    Route::put('/bible-studies/{bibleStudy}', [BibleStudyController::class, 'update']);
    Route::delete('/bible-studies/{bibleStudy}', [BibleStudyController::class, 'destroy']);

    // Testimonies Admin
    Route::get('/testimonies', [TestimonyController::class, 'index']);
    Route::put('/testimonies/{testimony}', [TestimonyController::class, 'update']);
    Route::patch('/testimonies/{testimony}/status', [TestimonyController::class, 'updateStatus']);
    Route::delete('/testimonies/{testimony}', [TestimonyController::class, 'destroy']);

    // Reviews Admin
    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::patch('/reviews/{review}/approve', [ReviewController::class, 'approve']);
    Route::patch('/reviews/{review}/toggle-featured', [ReviewController::class, 'toggleFeatured']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

    // Galleries CRUD
    Route::post('/galleries', [GalleryController::class, 'store']);
    Route::post('/galleries/{gallery}/images', [GalleryController::class, 'addImage']);
    Route::delete('/gallery-images/{galleryImage}', [GalleryController::class, 'removeImage']);
    Route::delete('/galleries/{gallery}', [GalleryController::class, 'destroy']);

    // Ministries CRUD
    Route::post('/ministries', [MinistryController::class, 'store']);
    Route::put('/ministries/{ministry}', [MinistryController::class, 'update']);
    Route::delete('/ministries/{ministry}', [MinistryController::class, 'destroy']);

    // Contacts Admin
    Route::get('/contacts', [ContactController::class, 'index']);
    Route::get('/contacts/{contactMessage}', [ContactController::class, 'show']);
    Route::patch('/contacts/{contactMessage}/read', [ContactController::class, 'markRead']);
    Route::post('/contacts/{contactMessage}/reply', [ContactController::class, 'reply']);
    Route::delete('/contacts/{contactMessage}', [ContactController::class, 'destroy']);

    // Newsletter Admin
    Route::get('/newsletter/subscribers', [NewsletterController::class, 'subscribers']);
    Route::get('/newsletter/subscribers/export', [NewsletterController::class, 'exportSubscribers']);
    Route::get('/newsletter/templates', [NewsletterController::class, 'templates']);
    Route::post('/newsletter/templates', [NewsletterController::class, 'storeTemplate']);
    Route::put('/newsletter/templates/{template}', [NewsletterController::class, 'updateTemplate']);
    Route::delete('/newsletter/templates/{template}', [NewsletterController::class, 'destroyTemplate']);
    Route::post('/newsletter/templates/{template}/send', [NewsletterController::class, 'sendTemplate']);

    // Announcements CRUD
    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::post('/announcements', [AnnouncementController::class, 'store']);
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update']);
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy']);

    // Pages CRUD
    Route::get('/pages', [PageController::class, 'index']);
    Route::post('/pages', [PageController::class, 'store']);
    Route::put('/pages/{page}', [PageController::class, 'update']);
    Route::delete('/pages/{page}', [PageController::class, 'destroy']);

    // Categories CRUD
    Route::get('/categories/admin', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    // Menus CRUD
    Route::get('/menus', [MenuController::class, 'index']);
    Route::post('/menus', [MenuController::class, 'store']);
    Route::put('/menus/{menu}', [MenuController::class, 'update']);
    Route::delete('/menus/{menu}', [MenuController::class, 'destroy']);

    // Roles CRUD
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{role}', [RoleController::class, 'update']);
    Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
    Route::post('/roles/assign', [RoleController::class, 'assignRole']);

    // Users CRUD
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);

    // Settings
    Route::put('/settings', [SettingController::class, 'update']);

    // Email Settings
    Route::get('/settings/email', [SettingController::class, 'emailSettings']);
    Route::put('/settings/email', [SettingController::class, 'updateEmailSettings']);
    Route::post('/settings/email/test', [SettingController::class, 'testEmail']);

    // Homepage Widget Config
    Route::get('/settings/widgets', [SettingController::class, 'widgetConfig']);
    Route::put('/settings/widgets', [SettingController::class, 'updateWidgetConfig']);

    // System & Deploy
    Route::get('/system/status', [SystemController::class, 'status']);
    Route::post('/system/git-pull', [SystemController::class, 'gitPull']);
    Route::post('/system/migrate', [SystemController::class, 'migrate']);
    Route::post('/system/build', [SystemController::class, 'buildAssets']);
    Route::post('/system/clear-cache', [SystemController::class, 'clearCache']);
    Route::post('/system/optimize', [SystemController::class, 'optimize']);
    Route::post('/system/deploy', [SystemController::class, 'deploy']);
    Route::get('/system/git-log', [SystemController::class, 'gitLog']);
});
