# Church Platform

A full-featured church website and content management system built with **Laravel 10**, **Sanctum API authentication**, **React admin panel**, and a **vanilla JS frontend SPA**.

---

## System Architecture Map

```
church_app/
└── church-platform/              # Laravel 10 Application
    ├── app/
    │   ├── Http/
    │   │   ├── Controllers/
    │   │   │   ├── Admin/
    │   │   │   │   └── AdminController        # Admin dashboard & views
    │   │   │   ├── Api/                        # REST API endpoints
    │   │   │   │   ├── AnnouncementController  # Announcements CRUD
    │   │   │   │   ├── BibleStudyController    # Bible studies CRUD
    │   │   │   │   ├── BlessingController      # Daily blessings CRUD
    │   │   │   │   ├── BookController          # Book library CRUD + PDF download
    │   │   │   │   ├── CategoryController      # Content categories CRUD
    │   │   │   │   ├── ContactController       # Contact form messages
    │   │   │   │   ├── DashboardController     # Admin dashboard stats
    │   │   │   │   ├── EventController         # Events CRUD + registration
    │   │   │   │   ├── GalleryController       # Photo galleries CRUD
    │   │   │   │   ├── MenuController          # Navigation menus CRUD
    │   │   │   │   ├── MinistryController      # Ministries CRUD
    │   │   │   │   ├── NewsletterController    # Newsletter subscriptions
    │   │   │   │   ├── PageController          # CMS pages CRUD
    │   │   │   │   ├── PostController          # Blog posts CRUD
    │   │   │   │   ├── PrayerRequestController # Prayer wall CRUD
    │   │   │   │   ├── ReviewController        # Church reviews CRUD
    │   │   │   │   ├── RoleController          # User roles & permissions
    │   │   │   │   ├── SermonController        # Sermons CRUD
    │   │   │   │   ├── SettingController       # Church settings
    │   │   │   │   ├── SitemapController       # SEO sitemap
    │   │   │   │   └── VerseController         # Verse of the day CRUD
    │   │   │   ├── Auth/
    │   │   │   │   └── AuthController          # Login, register, OAuth, profile
    │   │   │   └── Installer/
    │   │   │       └── InstallerController     # First-run setup wizard
    │   │   └── Middleware/
    │   │       ├── Authenticate                # Auth guard
    │   │       ├── AdminMiddleware             # Admin-only access
    │   │       ├── CheckInstalled              # Redirect to installer if not set up
    │   │       └── RedirectIfAuthenticated     # Guest-only pages
    │   └── Models/
    │       ├── User                # Users (name, email, password, role, OAuth provider)
    │       ├── Role                # Roles with JSON permissions array
    │       ├── Verse               # Daily verses
    │       ├── Blessing            # Daily blessings
    │       ├── PrayerRequest       # Community prayer wall
    │       ├── Event               # Church events
    │       ├── EventRegistration   # Event RSVPs
    │       ├── Post                # Blog posts (author, category, featured)
    │       ├── Sermon              # Sermon archive (audio/video URLs)
    │       ├── BibleStudy          # Bible study groups
    │       ├── Book                # Book library with PDF downloads
    │       ├── Review              # Church reviews with ratings
    │       ├── Gallery             # Photo galleries
    │       ├── GalleryImage        # Gallery images
    │       ├── Ministry            # Church ministries
    │       ├── Donation            # Online giving records
    │       ├── ContactMessage      # Contact form submissions
    │       ├── Newsletter          # Newsletter subscribers
    │       ├── Setting             # Church configuration (singleton)
    │       ├── Announcement        # Ticker announcements
    │       ├── Page                # CMS pages
    │       ├── Category            # Content categories
    │       └── Menu                # Navigation menus (JSON items)
    │
    ├── config/
    │   ├── auth.php               # Guards: web (session) + sanctum (token)
    │   ├── sanctum.php            # API token config, stateful domains
    │   ├── services.php           # Google & Facebook OAuth credentials
    │   └── cors.php               # CORS: api/* + sanctum/csrf-cookie
    │
    ├── database/migrations/       # 21 migration files
    │   ├── 000001_create_users_table
    │   ├── 000002_create_password_reset_tokens_table
    │   ├── 000003_create_personal_access_tokens_table
    │   ├── 000004_create_settings_table
    │   ├── 000005_create_verses_table
    │   ├── 000006_create_blessings_table
    │   ├── 000007_create_prayer_requests_table
    │   ├── 000008_create_events_table
    │   ├── 000009_create_event_registrations_table
    │   ├── 000010_create_reviews_table
    │   ├── 000011_create_books_table
    │   ├── 000012_create_bible_studies_table
    │   ├── 000013_create_posts_table
    │   ├── 000014_create_sermons_table
    │   ├── 000015_create_galleries_table  (+ gallery_images)
    │   ├── 000016_create_ministries_table
    │   ├── 000017_create_donations_table
    │   ├── 000018_create_contact_messages_table
    │   ├── 000019_create_newsletters_table
    │   ├── 000020_add_theme_and_widget_config_to_settings
    │   └── 000021_add_missing_columns_and_new_tables
    │         (roles, announcements, pages, categories, menus tables
    │          + role_id, provider, provider_id on users)
    │
    ├── resources/
    │   ├── views/
    │   │   ├── welcome.blade.php              # Frontend SPA shell
    │   │   ├── partials/
    │   │   │   ├── frontend-html.blade.php    # All HTML: nav, pages, modals, auth modal
    │   │   │   ├── frontend-css.blade.php     # All CSS: dark/light themes, responsive
    │   │   │   └── frontend-js.blade.php      # All JS: API calls, navigation, auth
    │   │   ├── auth/
    │   │   │   └── login.blade.php            # Admin login page
    │   │   ├── layouts/
    │   │   │   └── admin.blade.php            # Admin panel layout (sidebar + Alpine.js)
    │   │   ├── admin/
    │   │   │   ├── dashboard.blade.php        # Admin dashboard
    │   │   │   └── manage.blade.php           # Dynamic admin section (loads React)
    │   │   └── installer/                     # Setup wizard views
    │   │       ├── welcome.blade.php
    │   │       ├── database.blade.php
    │   │       ├── admin.blade.php
    │   │       ├── church.blade.php
    │   │       └── finalize.blade.php
    │   └── js/
    │       ├── app.jsx                        # React entry point
    │       └── components/
    │           ├── AdminApp.jsx               # React admin router
    │           ├── shared/
    │           │   ├── api.js                 # API helper (fetch wrapper)
    │           │   └── CrudPanel.jsx          # Reusable CRUD table component
    │           └── admin/                     # Admin panel managers
    │               ├── AnnouncementsManager.jsx
    │               ├── BibleStudiesManager.jsx
    │               ├── BlessingsManager.jsx
    │               ├── BooksManager.jsx
    │               ├── CategoriesManager.jsx
    │               ├── ContactsManager.jsx
    │               ├── DonationsManager.jsx
    │               ├── EventsManager.jsx
    │               ├── GalleriesManager.jsx
    │               ├── MenuManager.jsx
    │               ├── MinistriesManager.jsx
    │               ├── NewsletterManager.jsx
    │               ├── PagesManager.jsx
    │               ├── PostsManager.jsx
    │               ├── PrayersManager.jsx
    │               ├── ReviewsManager.jsx
    │               ├── RolesManager.jsx
    │               ├── SermonsManager.jsx
    │               ├── SettingsManager.jsx
    │               ├── UsersManager.jsx
    │               └── VersesManager.jsx
    │
    ├── routes/
    │   ├── web.php                # Web routes: login, admin, social auth, SPA catch-all
    │   └── api.php                # API routes: public content + auth:sanctum CRUD
    │
    ├── public/
    │   ├── index.php              # Laravel entry point
    │   ├── manifest.json          # PWA manifest
    │   └── sw.js                  # Service worker (offline support)
    │
    ├── composer.json              # PHP dependencies
    ├── package.json               # JS dependencies (React, Vite, Tailwind)
    ├── vite.config.js             # Vite build config
    └── .env.example               # Environment template
```

---

## Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                     FRONTEND (Browser)                       │
│                                                              │
│  ┌──────────────────┐       ┌──────────────────────────┐    │
│  │   Public Website  │       │     Admin Panel           │    │
│  │   (Vanilla JS)    │       │     (React + Blade)       │    │
│  │                   │       │                           │    │
│  │  - Home (verse,   │       │  - Dashboard (stats)      │    │
│  │    blessing,      │       │  - Content Managers        │    │
│  │    announcements) │       │    (21 CRUD panels)       │    │
│  │  - Events         │       │  - Settings               │    │
│  │  - Prayer Wall    │       │  - User/Role Management   │    │
│  │  - Library        │       │                           │    │
│  │  - Bible Study    │       │  Auth: Session-based      │    │
│  │  - Sermons        │       │  (Blade login form)       │    │
│  │  - Giving         │       └────────┬─────────────────┘    │
│  │  - Ministries     │                │                      │
│  │  - Reviews        │                │                      │
│  │  - About          │                │                      │
│  │                   │                │                      │
│  │  Auth: Token-based│                │                      │
│  │  (AJAX modal +    │                │                      │
│  │   Social OAuth)   │                │                      │
│  └────────┬──────────┘                │                      │
│           │                           │                      │
└───────────┼───────────────────────────┼──────────────────────┘
            │ API calls (JSON)          │ Session + CSRF
            │ Bearer token              │
            ▼                           ▼
┌─────────────────────────────────────────────────────────────┐
│                    LARAVEL BACKEND                            │
│                                                              │
│  ┌─────────────┐  ┌──────────────┐  ┌───────────────────┐  │
│  │  API Routes  │  │  Web Routes   │  │  Auth System       │  │
│  │  /api/*      │  │  /login       │  │                    │  │
│  │              │  │  /admin/*     │  │  - Sanctum tokens  │  │
│  │  Public:     │  │  /auth/{p}/   │  │  - Session auth    │  │
│  │  GET content │  │    redirect   │  │  - Google OAuth    │  │
│  │  POST forms  │  │  /auth/{p}/   │  │  - Facebook OAuth  │  │
│  │              │  │    callback   │  │  - Role-based ACL  │  │
│  │  Protected:  │  │  /{any} (SPA) │  │                    │  │
│  │  Full CRUD   │  │              │  │                    │  │
│  └──────┬──────┘  └──────┬───────┘  └────────────────────┘  │
│         │                │                                    │
│         ▼                ▼                                    │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │              Eloquent ORM (22 Models)                    │ │
│  └────────────────────────┬────────────────────────────────┘ │
│                           │                                   │
└───────────────────────────┼───────────────────────────────────┘
                            │
                            ▼
                   ┌─────────────────┐
                   │     MySQL        │
                   │   Database       │
                   │                  │
                   │  21 tables       │
                   │  (see migrations)│
                   └─────────────────┘
```

---

## Authentication System

```
┌─────────────────────────────────────────────┐
│              Authentication Flows            │
├─────────────────────────────────────────────┤
│                                             │
│  1. EMAIL/PASSWORD (Frontend AJAX Modal)    │
│     User clicks person icon in navbar       │
│     ──> Modal opens (Sign In / Sign Up)     │
│     ──> POST /api/login or /api/register    │
│     ──> Receives Sanctum token              │
│     ──> Stored in localStorage              │
│     ──> Avatar initials shown in navbar     │
│                                             │
│  2. SOCIAL OAUTH (Google / Facebook)        │
│     User clicks Google/Facebook button      │
│     ──> GET /auth/google/redirect           │
│     ──> Redirected to provider              │
│     ──> Callback: GET /auth/google/callback │
│     ──> User created/linked in DB           │
│     ──> Redirect to /?auth_token=...        │
│     ──> JS picks up token from URL          │
│     ──> Stored in localStorage              │
│                                             │
│  3. ADMIN SESSION (Blade Login Page)        │
│     Navigate to /login                      │
│     ──> Blade form POST /login              │
│     ──> Session created                     │
│     ──> Redirect to /admin                  │
│                                             │
│  4. ROLE-BASED ACCESS CONTROL               │
│     User ──> hasPermission(perm)            │
│       ├── is_admin? ──> Allow all           │
│       └── role.permissions includes perm?   │
│                                             │
└─────────────────────────────────────────────┘
```

---

## Model Relationships

```
User
 ├── belongsTo ──> Role (role_id)
 ├── hasMany ───> Post (author_id)
 ├── hasMany ───> PrayerRequest
 ├── hasMany ───> Review
 ├── hasMany ───> BibleStudy (author_id)
 └── hasMany ───> Sermon (author_id)

Role
 └── hasMany ───> User
     permissions: JSON array (e.g. ["manage_posts", "manage_events"])

Gallery
 └── hasMany ───> GalleryImage

Event
 └── hasMany ───> EventRegistration

Post
 └── belongsTo ──> User (author_id)
     belongsTo ──> Category

Setting ──> singleton (one row, all church config)
Menu ──> items stored as JSON array
```

---

## Frontend Pages (SPA)

| Page | Route | Data Source |
|------|-------|-------------|
| Home | `#home` | Verse of day, blessing, announcements, posts, prayers, events, latest sermon |
| Events | `#events` | `/api/events/upcoming` |
| Prayers | `#prayers` | `/api/prayer-requests/public` |
| Library | `#library` | `/api/books/featured` |
| Bible Study | `#studies` | `/api/bible-studies/featured` |
| Sermons | `#sermons` | `/api/sermons/featured` |
| Giving | `#giving` | Static donation form |
| Ministries | `#ministries` | `/api/ministries` |
| Reviews | `#reviews` | `/api/reviews/approved` |
| About | `#about` | `/api/settings` |

---

## API Endpoints

### Public (no auth required)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/verses/today` | Today's verse |
| GET | `/api/blessings/today` | Today's blessing |
| GET | `/api/announcements/active` | Active announcements |
| GET | `/api/events/upcoming` | Upcoming events |
| GET | `/api/posts/published` | Published posts |
| GET | `/api/posts/featured` | Featured posts |
| GET | `/api/posts/{slug}` | Single post |
| GET | `/api/sermons/featured` | Featured sermons |
| GET | `/api/sermons/{slug}` | Single sermon |
| GET | `/api/books/featured` | Featured books |
| GET | `/api/books/{book}` | Single book |
| GET | `/api/books/{book}/download` | Download book PDF |
| GET | `/api/bible-studies/featured` | Featured studies |
| GET | `/api/reviews/approved` | Approved reviews |
| GET | `/api/galleries` | All galleries |
| GET | `/api/ministries` | All ministries |
| GET | `/api/prayer-requests/public` | Public prayer requests |
| GET | `/api/settings` | Church settings |
| GET | `/api/pages/published` | Published CMS pages |
| GET | `/api/categories` | All categories |
| GET | `/api/menus/{location}` | Menu by location |
| POST | `/api/register` | Register new user |
| POST | `/api/login` | Login (returns token) |
| POST | `/api/contact` | Submit contact form |
| POST | `/api/newsletter/subscribe` | Subscribe to newsletter |
| POST | `/api/prayer-requests` | Submit prayer request |
| POST | `/api/reviews` | Submit review |
| POST | `/api/events/{event}/register` | Register for event |

### Protected (auth:sanctum required)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/logout` | Revoke token |
| GET | `/api/profile` | Get user profile |
| PUT | `/api/profile` | Update profile |
| GET | `/api/dashboard/stats` | Admin dashboard stats |
| CRUD | `/api/verses` | Verse management |
| CRUD | `/api/blessings` | Blessing management |
| CRUD | `/api/prayer-requests` | Prayer request management |
| CRUD | `/api/events` | Event management |
| CRUD | `/api/posts` | Post management |
| CRUD | `/api/sermons` | Sermon management |
| CRUD | `/api/books` | Book management |
| CRUD | `/api/bible-studies` | Bible study management |
| CRUD | `/api/reviews` | Review management |
| CRUD | `/api/galleries` | Gallery management |
| CRUD | `/api/ministries` | Ministry management |
| CRUD | `/api/announcements` | Announcement management |
| CRUD | `/api/pages` | CMS page management |
| CRUD | `/api/categories` | Category management |
| CRUD | `/api/menus` | Menu management |
| CRUD | `/api/roles` | Role management |
| PUT | `/api/settings` | Update church settings |

---

## How to Update an Existing Church App from GitHub

### Prerequisites
- PHP 8.1+
- Composer 2.x
- Node.js 18+ & npm
- MySQL 5.7+ or MariaDB 10.3+

### Step-by-step Update Guide

#### 1. Backup your existing installation

```bash
# Backup database
mysqldump -u root -p church_platform > backup_$(date +%Y%m%d).sql

# Backup .env file (contains your secrets)
cp church-platform/.env church-platform/.env.backup

# Backup any uploaded files
cp -r church-platform/storage/app/public church-platform/storage/app/public.backup
```

#### 2. Pull latest changes from GitHub

```bash
# Navigate to your project
cd /path/to/church_app

# Stash any local changes you want to keep
git stash

# Pull latest from main branch
git pull origin main

# Re-apply your local changes (if any)
git stash pop
```

#### 3. Install/update dependencies

```bash
cd church-platform

# Update PHP dependencies
composer install --no-dev --optimize-autoloader

# Update JS dependencies and build assets
npm install
npm run build
```

#### 4. Run database migrations

```bash
# Run any new migrations
php artisan migrate

# If you get errors, you can check status first:
php artisan migrate:status
```

#### 5. Clear all caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild optimized cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 6. Check for new environment variables

```bash
# Compare your .env with .env.example to see if new vars were added
diff church-platform/.env church-platform/.env.example
```

Key environment variables to check after updates:
```env
# Social Auth (added recently)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URL="${APP_URL}/auth/google/callback"

FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URL="${APP_URL}/auth/facebook/callback"
```

#### 7. Set correct permissions

```bash
chmod -R 775 church-platform/storage
chmod -R 775 church-platform/bootstrap/cache
chown -R www-data:www-data church-platform/storage
chown -R www-data:www-data church-platform/bootstrap/cache
```

---

## Fresh Installation

#### 1. Clone the repository

```bash
git clone <your-repo-url> church_app
cd church_app/church-platform
```

#### 2. Install dependencies

```bash
composer install
npm install
npm run build
```

#### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database credentials:
```env
DB_DATABASE=church_platform
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

#### 4. Run the web installer

Navigate to `http://your-domain/install` in your browser. The setup wizard will guide you through:
1. Database connection setup
2. Admin account creation
3. Church information configuration
4. Final installation (runs migrations + seeds)

#### 5. (Alternative) Manual setup without installer

```bash
php artisan migrate
php artisan db:seed  # if seeders exist
```

Then create an admin user:
```bash
php artisan tinker
>>> User::create(['name'=>'Admin', 'email'=>'admin@church.com', 'password'=>bcrypt('password'), 'is_admin'=>true]);
```

---

## Setting Up Social Login (Google & Facebook)

### Google OAuth
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a project > APIs & Services > Credentials
3. Create OAuth 2.0 Client ID (Web application)
4. Add authorized redirect URI: `https://your-domain.com/auth/google/callback`
5. Copy Client ID and Client Secret to `.env`

### Facebook OAuth
1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create an App > Facebook Login
3. Add Valid OAuth Redirect URI: `https://your-domain.com/auth/facebook/callback`
4. Copy App ID and App Secret to `.env`

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 10 (PHP 8.1+) |
| API Auth | Laravel Sanctum (token-based) |
| Session Auth | Laravel Sessions (admin panel) |
| Social Auth | Laravel Socialite (Google, Facebook) |
| Database | MySQL / MariaDB |
| Frontend | Vanilla JavaScript (SPA-like) |
| Admin Panel | React 18 + Tailwind CSS |
| Build Tool | Vite 5 |
| CSS | Custom CSS variables (dark/light themes) |
| PWA | Service Worker + Web App Manifest |
| Fonts | Playfair Display, Source Sans 3, Cormorant Garamond |
