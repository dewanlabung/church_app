# Church Community Platform — Laravel Engine + React PWA

> **Architecture doctrine:** Plugin-first. Every feature is a self-contained module
> loaded via service providers. The core engine never changes — you add, enable,
> disable, or remove plugins without touching core.

---

## Tech Stack

| Layer | Technology | Reason |
|---|---|---|
| Backend | Laravel 11 + Octane (FrankenPHP) | Performance, async, plugin ecosystem |
| API | Laravel Sanctum + `/api/v1/` versioning | SPA auth, mobile-ready |
| Queue | Laravel Horizon + Redis | Background jobs, notifications |
| Cache | Redis (L1) + Laravel Cache (L2) | Feed caching, rate limiting |
| Search | Laravel Scout + MeiliSearch | Fast local full-text search |
| Frontend | React 18 + TypeScript + Vite | PWA, component-based |
| PWA | Workbox + vite-plugin-pwa | Offline support, install prompt |
| State | Zustand + React Query | Global state + server caching |
| UI | Tailwind CSS + shadcn/ui | Fast, themeable, dark/light |
| DB | MySQL 8 + Redis (sessions/cache) | |
| Storage | Local + S3-compatible (configurable) | |
| Realtime | Laravel Reverb (WebSocket) | Chat, live notifications |

---

## Plugin System (WordPress-style)

### Plugin Manager — Enable / Disable / Remove

Admin panel Plugin Manager (like WordPress Plugins page):
- **Enable** — boots ServiceProvider, registers routes, runs pending migrations
- **Disable** — deregisters routes, hides UI, DB tables/data preserved
- **Remove** — disable + delete folder + optional migration rollback
- **Install** — drop folder into `plugins/` → appears as Inactive → click Enable

**Runtime registry** (`storage/app/plugins.json`):
```json
{
  "Timeline":   { "enabled": true,  "version": "1.0.0" },
  "Post":       { "enabled": true,  "version": "1.0.0" },
  "Library":    { "enabled": false, "version": "1.2.0" },
  "Hymns":      { "enabled": false, "version": "0.9.0" }
}
```

`PluginManager` (booted in `AppServiceProvider`) only boots enabled plugins.
Toggle = update JSON + clear cache. No server restart needed.

**`plugin.json` manifest (each plugin has one):**
```json
{
  "name": "Daily Verse",
  "slug": "DailyVerse",
  "version": "1.0.0",
  "description": "Displays a daily Bible verse on feed and login page.",
  "author": "Your Name",
  "icon": "book-open",
  "category": "Content",
  "requires": ["Post"],
  "optional": ["Notification"],
  "settings_page": true,
  "can_disable": true,
  "can_remove": true
}
```

**Core plugins** (`can_disable: false`): Auth, Settings, User — always on.

---

### Theme Manager — Add / Remove / Activate

Admin panel Theme Manager:
- **Activate** a theme site-wide
- **Preview** in sandboxed iframe before activating
- **Install** by uploading `.zip` → extracted to `themes/`
- **Remove** (cannot remove active theme)
- **Appearance Customizer** per theme

**`theme.json` manifest:**
```json
{
  "name": "Default",
  "slug": "default",
  "version": "1.0.0",
  "description": "Clean church community theme.",
  "supports": ["dark_mode", "custom_colors", "custom_fonts", "logo"],
  "colors": {
    "primary":    "#4F46E5",
    "accent":     "#7C3AED",
    "background": "#FFFFFF",
    "surface":    "#F3F4F6"
  },
  "fonts": { "heading": "Inter", "body": "Inter" }
}
```

**Runtime theming flow:**
```
1. Active theme slug stored in settings table
2. GET /api/v1/theme/css → ThemeService reads variables.css + admin overrides
3. React injects into <head> on load
4. All components use CSS vars: var(--color-primary), var(--font-body)
5. Dark/Light → user_meta + localStorage, toggled client-side
6. Theme change → clears CSS cache → frontend refetches
```

**Appearance Customizer (admin live editor):**
- Live preview iframe
- Color pickers (primary, accent, background, surface, text)
- Font picker (Google Fonts API)
- Logo upload: light + dark versions, favicon
- Custom CSS and Custom JS textareas (head/body placement)
- Save → writes to `settings` table + clears cache

---

### Directory Structure

```
church-platform/
├── app/
│   └── Core/
│       ├── PluginManager.php       ← discovers + boots enabled plugins
│       ├── ThemeManager.php        ← resolves active theme + CSS
│       ├── SettingsManager.php     ← cached global key/value store
│       └── MenuBuilder.php         ← builds nav menus from DB config
├── plugins/                        ← all plugin folders (PSR-4 autoloaded)
│   ├── Auth/           (core — always on)
│   ├── Settings/       (core — always on)
│   ├── Timeline/
│   ├── Post/
│   ├── DailyVerse/
│   ├── Greeting/
│   ├── Notification/
│   ├── ChurchPage/
│   ├── Community/
│   ├── Events/
│   ├── BibleStudy/
│   ├── Prayer/
│   ├── Question/
│   ├── Chat/
│   ├── Search/
│   ├── Newsletter/
│   ├── Analytics/
│   ├── Ads/
│   ├── Gdpr/
│   ├── Sitemap/
│   ├── Library/        ← stub (disabled by default)
│   ├── Hymns/          ← stub (disabled by default)
│   └── BibleReader/    ← stub (disabled by default)
├── themes/
│   ├── default/
│   ├── minimal/
│   └── christmas/
├── storage/app/
│   └── plugins.json    ← runtime enable/disable state
└── resources/js/
    ├── app.tsx
    ├── router.tsx
    ├── layouts/
    ├── components/ui/      ← shadcn base
    ├── components/shared/  ← PostCard, Avatar, FeedItem, etc.
    ├── stores/             ← Zustand: authStore, themeStore, settingsStore
    ├── hooks/              ← useAuth, useSettings, useFeed
    └── plugins/            ← lazy React components per plugin
```

**PSR-4 autoloading** (`composer.json`):
```json
"autoload": {
  "psr-4": {
    "App\\": "app/",
    "Plugins\\": "plugins/"
  }
}
```

---

## Database Schema (Key Tables)

```sql
-- Core
users, user_meta, user_roles, permissions, role_permissions
sessions, oauth_tokens, email_verifications

-- Social Engine
posts, post_media, post_reactions (type: like/bless/pray/amen)
comments, comment_reactions, shares, saves, reports
feeds (materialized feed cache per user)
hashtags, post_hashtags

-- Church Pages (replaces Fan Pages)
church_pages, church_page_members, church_page_meta
church_schedules, church_locations, church_seo_meta

-- Communities (replaces Groups)
communities, community_members, community_roles
community_posts (extends posts), community_rules

-- Church Features
events, event_attendees, event_reminders
bible_studies, bible_study_sessions, bible_study_members
prayer_requests, prayer_request_supports
questions, question_answers, question_votes
daily_verses

-- Settings & System
settings (key/value global), theme_settings, menu_configs
ad_placements, analytics_events, notifications_log
newsletter_subscribers, newsletter_templates
custom_fields, custom_field_values
user_notification_prefs
```

---

## 5 User Roles

| Role | Permissions |
|---|---|
| `super_admin` | Full system control, all settings |
| `church_admin` | Manage church page + community, members |
| `counsellor` | Private messaging, prayer management, pastoral notes |
| `musician` | Events, worship content, WorshipPlanner plugin |
| `general_user` | Standard social features, posts, reactions |

Custom fields per user type. Managed via Spatie Permission + admin UI.

---

## Sprint Plan

| Sprint | Focus | Deliverable |
|---|---|---|
| **1** | Core Engine + Plugin Loader + Auth | Login/register, plugin system, 5 user roles |
| **2** | React PWA Shell + Theme + Greeting | Installable PWA, dark/light, login greeting |
| **3** | Post Engine + Feed + Daily Verse | Create posts, timeline feed, verse widget |
| **4** | Reactions + Comments + Share | Full social interactions |
| **5** | Notification Engine (all channels) | In-app, email, push for all events |
| **6** | Prayer + Questions + Bible Study | Church-specific post types |
| **7** | Church SEO Pages | Public church profiles with SEO |
| **8** | Communities | Church communities with all features |
| **9** | Events Module | Create, RSVP, reminders |
| **10** | Backend Settings Panel | Full admin settings (all categories) |
| **11** | Search + Import + Sitemaps | MeiliSearch, CSV import |
| **12** | Performance + GDPR + ADS | Caching, compliance, monetization |

---

## Phase 1 — Core Engine (Sprint 1)

**Goal:** Bare-metal Laravel + plugin loader + auth + DB.

1. `composer create-project laravel/laravel church-platform`
2. Install core packages (Octane, Sanctum, Horizon, Reverb, Scout, Spatie, MediaLibrary)
3. Build `PluginManager` + `SettingsManager` + `ThemeManager`
4. Auth plugin: registration (email confirm toggle), login (single-device toggle), OAuth
5. 5 user roles via Spatie Permission
6. Custom user fields (`custom_fields` table, rendered dynamically)
7. Localization (Laravel Lang, per-user locale in `user_meta`)
8. Versioned API foundation (`/api/v1/`, rate limiting, response macros)

---

## Phase 2 — React PWA Shell (Sprint 2)

**Goal:** Fast installable PWA with routing, auth, theming, greeting engine.

1. Vite + React 18 + TypeScript in `resources/js/`
2. `vite-plugin-pwa` + Workbox — offline caching
3. React Router v6 with lazy route modules
4. Zustand stores: `authStore`, `themeStore`, `settingsStore`
5. React Query for all API calls with optimistic updates
6. Tailwind + shadcn/ui component library
7. Layout shell: TopNav, Sidebar (desktop), BottomNav (mobile), RightPanel
8. Admin panel: separate `/admin/*` route tree, role-guarded
9. Theme injection: CSS vars from `GET /api/v1/theme/css`

### Login Greeting Engine (Plugin: `Greeting`)

After login → greeting modal/banner (configurable in admin):
- Welcome message: "Welcome back, {first_name}!"
- Show today's daily verse
- Show upcoming events (next 3)
- Show unanswered prayer requests
- Random scripture encouragement

**Admin controls:**
- Enable/Disable entirely
- Style: Modal | Top banner | Sidebar card
- Template with tokens: `{first_name}`, `{day_of_week}`, `{verse}`
- Time-based variants: morning / afternoon / evening
- Frequency: Every login | Once per day | Once per session

**Extensible:** `GreetingContentResolver` interface — any future plugin can push blocks.

---

## Phase 3 — Core Social Modules (Sprints 3-4)

Following the Sngine feed flowchart pattern:

### 3A. Post Engine

Post types: text, photo, video, feeling/activity, poll, link-preview, document

Church-specific post types (on top of base):
- `blessing` — share a blessing story
- `prayer_request` — request with optional anonymity
- `ask_question` — Q&A format
- `bible_verse` — verse with reflection
- `event_share` — event card share

Post privacy: Public | Church Community | Friends | Only Me

**Reactions:** Like / Bless (hands) / Amen / Pray / Love

Comment tree (2 levels), reactions on comments, inline editing.

Share: external, internal reshare, share to community.

Save/Bookmark posts.

### 3B. Timeline Feed (Fan-out on Write)

- Pre-computed per user in `feeds` table on post creation
- `FanOutPost` background job triggers on: new post, new follow, community join
- Infinite scroll (cursor pagination)
- Feed filters: All | Church Family | Prayer Wall | Bible | Events

### 3C. Prayer Request Module
- Anonymity toggle on prayer posts
- Support counter ("I'm praying for you")
- Counsellor role can respond privately
- Prayer wall feed filter

### 3D. Ask Question Module
- Q&A: question → threaded answers → best answer mark
- Voting on answers

### 3E. Bible Study Module
- Sessions: title, description, scripture, date/time, recurring
- Session discussion threads, per-user private notes
- Bible API integration for verse lookups

### 3F. Events Module
- Create: title, description, physical location + online link, recurring
- RSVP: Going / Interested / Not Going
- Reminders: 24h + 1h before (notification + email)
- Calendar view + list view
- Attach to Church Page or Community

---

## Phase 4 — Church SEO Pages (Sprint 7)

**Replaces Fan Pages.** Each church gets `/@churchname`.

- Profile: name, logo, banner, about, founding date, denomination
- Location (map embed), service schedule
- Contact info, website, social links
- Scoped feed: posts, events, bible studies, prayer wall
- Sermons/media uploads
- Member directory (opt-in)
- Follow / Join church

**SEO engine:**
- Auto meta title/description, OG tags
- JSON-LD schema: `Organization`, `Church`, `Event`
- Auto-included in `/sitemap-churches.xml`

Managed by `church_admin` role.

---

## Phase 5 — Church Communities (Sprint 8)

**Replaces Groups.** Church-branded community spaces.

- Create: name, description, type (Public / Private / Hidden), church association
- Roles: Owner, Admin, Moderator, Member
- Full post types scoped to community
- Community Events, Bible Studies, Prayer Wall
- Community rules (pinned), join requests, invitation links
- Discovery page with search + categories

---

## Phase 6 — Notification Engine (Sprint 5)

**Every action fires a typed notification. All channels configurable per user + globally.**

| Event | In-App | Email | Push | SMS |
|---|---|---|---|---|
| Post reaction | ✅ | toggle | toggle | — |
| Comment on post | ✅ | toggle | toggle | — |
| Reply to comment | ✅ | toggle | toggle | — |
| New follower | ✅ | toggle | — | — |
| Prayer request support | ✅ | toggle | toggle | — |
| Question answered | ✅ | toggle | toggle | — |
| Best answer selected | ✅ | toggle | — | — |
| Event reminder (24h + 1h) | ✅ | ✅ | ✅ | optional |
| Bible study starting | ✅ | toggle | toggle | — |
| Community invite | ✅ | toggle | — | — |
| Church page post | ✅ | toggle | toggle | — |
| Daily verse | — | toggle | toggle | — |
| Login from new device | ✅ | ✅ | — | optional |
| @mention | ✅ | toggle | toggle | — |
| Admin announcement | ✅ | toggle | toggle | — |

**Architecture:**
```
Action Happens
  → Fire Laravel Event (PostReacted::class)
  → NotificationDispatcher listener catches it
  → Writes to notifications_log table
  → Dispatches per-channel jobs:
      InAppNotificationJob  → Reverb WebSocket push
      EmailNotificationJob  → Queued mailable
      PushNotificationJob   → Web Push (VAPID)
```

**Admin controls:** master on/off per channel, throttle (max N/hour), template editor, batch announcements.

**User controls:** per-type toggles stored in `user_notification_prefs`.

---

## Phase 7 — Daily Verse Engine (Sprint 3)

Plugin: `DailyVerse`

Table: `daily_verses` — `date`, `reference`, `text`, `version`, `is_active`

Admin can:
- **Import CSV**: `date,reference,text,version` — bulk upload with preview + validation
- **Export sample CSV**: downloadable skeleton for correct format
- **Auto-schedule**: falls back to random from pool if no verse assigned for a date

API: `GET /api/v1/verse/today` — cached in Redis, expires midnight

Settings:
- Show widget on home feed (on/off)
- Show on login/register page (on/off)
- Daily push notification at 6am (on/off)

**CSV Import flow:**
```
Admin uploads CSV
  → ImportDailyVersesJob queued
  → Validates each row (date format, required fields)
  → Skips duplicates
  → Reports success/fail counts
  → Admin receives email summary
```

---

## Phase 8 — Backend Settings Panel (Sprint 10)

All settings scoped in categories. Stored in `settings` key/value table. Cached.

### General
Site name, tagline, app URL, favicon, logo (light/dark), timezone, date format.

### Speed & Cache
Cache driver (file/redis/memcached), TTL, page cache toggle, Octane workers, clear cache button.

### Security
Cloudflare Turnstile / reCAPTCHA (placement: registration/login/contact), CSRF, rate limiting, IP blacklist.

### SEO Engine
Default meta template, Robots.txt editor, sitemap generator, OG image, Twitter card.

### Appearance Customizer
Theme selection, color pickers, font selector, logo/favicon upload, custom CSS/JS.

### Menu Builder
Primary nav, Mobile menu, Auth dropdown, Admin sidebar.
Drag-and-drop. JSON stored in `menu_configs`. Role-visibility per item.

### Social Logins
Google OAuth, Facebook Login, Apple Sign-In — each togglable with key/secret fields.

### Email & Newsletter
SMTP / Mailgun / SES / Postmark config. Newsletter template builder (HTML drag editor).
Subscriber management: import CSV, unsubscribe link, GDPR consent.

### Upload & Storage
Driver: local / S3 / Cloudflare R2 / DigitalOcean Spaces.
Max upload sizes per type, allowed extensions, image resize presets.

### Authentication
Email confirmation (on/off), registration open/closed, single-device login, password policy, self-delete (GDPR).

### Users & Roles
5 user types management, custom permissions (Spatie UI), custom profile fields builder, user CSV import.

### Localization
Default language, enabled language list, RTL support toggle per language.

### Analytics
Google Analytics 4 tracking ID, internal analytics (page views + post engagement), admin dashboard widget.

### GDPR
Cookie consent banner, privacy/terms URLs, data export (user downloads ZIP), deletion request queue.

### ADS
Placements: feed top, feed between, sidebar, church page banner.
HTML/JS ad code per placement. Role suppression (admins/premium = no ads).

### System
Update checker, PHP/queue/Horizon status, maintenance mode toggle, backup trigger (DB + files ZIP).

---

## Phase 9 — Search & Import (Sprint 11)

### MeiliSearch (via Laravel Scout)
Searchable: Users, Posts, Church Pages, Communities, Events, Bible Studies.
Unified search bar with tabbed results. `php artisan scout:import` per model.

### CSV Import
Users, Church Pages, Communities — field mapping UI in admin.
Background job with SSE progress stream. Email summary on complete.

---

## Phase 10 — Performance & Scalability (Sprint 12 + ongoing)

- **Feed**: Fan-out-on-write, Redis per-user cache (24h TTL, regenerate on access)
- **API**: Response caching with cache tags (busted on write)
- **Images**: WebP conversion via queue job, CDN-ready URLs
- **Queues**: Separate Horizon queues: `feeds`, `media`, `notifications`, `email`, `imports`
- **Octane**: Keep-alive workers, shared app state
- **DB**: Query caching for settings, read replica config (optional)
- **Rate Limiting**: Per-role API limits (stricter for guests)
- **Frontend**: All plugin React components lazy-loaded on route entry

---

## Future Expandable Plugins (Stubs Ready, Disabled by Default)

| Plugin | Description |
|---|---|
| `Library` | PDF reader — books, sermons, devotionals (PDF.js) |
| `Hymns` | Hymn lyrics browser, key/transpose tool, playlist |
| `BibleReader` | Full Bible — bookmarks, highlights, notes, verse sharing |
| `Sermons` | Audio/video archive, podcast RSS feed |
| `Giving` | Online offering/tithe (Stripe/PayPal) |
| `Counselling` | Private session booking for counsellors |
| `WorshipPlanner` | Set list builder for musicians |
| `Certificates` | Auto-generate Bible study completion certificates |
| `LeaderBoard` | Engagement points, badges, gamification |
| `LiveStream` | Embed YouTube/Zoom/Vimeo live service link |

Each activates by dropping folder into `plugins/` and clicking Enable. Zero core changes.

---

## Coding Start Guide

### Prerequisites
```
PHP 8.3+, Composer 2, Node 20+, MySQL 8, Redis
Laravel Herd (macOS) or Laragon (Windows) — bundles all of the above
MeiliSearch: docker run -d -p 7700:7700 getmeili/meilisearch
```

### Step 1 — Bootstrap
```bash
composer create-project laravel/laravel church-platform
cd church-platform
```

### Step 2 — Core Packages
```bash
composer require \
  laravel/octane \
  laravel/sanctum \
  laravel/horizon \
  laravel/reverb \
  laravel/scout \
  spatie/laravel-permission \
  spatie/laravel-medialibrary \
  spatie/laravel-sitemap \
  intervention/image-laravel \
  league/csv \
  maatwebsite/excel
```

### Step 3 — Frontend
```bash
npm install react react-dom @types/react @types/react-dom
npm install typescript @vitejs/plugin-react
npm install tailwindcss @tailwindcss/vite
npm install zustand @tanstack/react-query react-router-dom
npm install vite-plugin-pwa workbox-window
npm install -D @types/node
```

### Step 4 — `.env`
```env
APP_NAME="Church Platform"
APP_URL=http://localhost
DB_DATABASE=church_platform
REDIS_HOST=127.0.0.1
QUEUE_CONNECTION=redis
BROADCAST_CONNECTION=reverb
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://localhost:7700
```

### Step 5 — First files to write (in order)
1. `app/Core/PluginManager.php`
2. `app/Core/SettingsManager.php`
3. `app/Core/ThemeManager.php`
4. `app/Core/MenuBuilder.php`
5. `config/plugins.php` (core plugin list)
6. `storage/app/plugins.json` (initial state)
7. `plugins/Auth/` — AuthServiceProvider, migrations, controllers
8. `plugins/Settings/` — SettingsServiceProvider, admin routes
9. `database/migrations/` — all core tables
10. `resources/js/app.tsx` + `router.tsx`
11. `resources/js/layouts/ShellLayout.tsx`
12. `plugins/Timeline/` + `resources/js/plugins/timeline/`

**Rule:** Build + test backend API endpoint first (curl/Postman), then build React component for it.

---

## Verification Checkpoints

| After Sprint | Check |
|---|---|
| 1 | `php artisan test`, all auth endpoints respond |
| 2 | Lighthouse PWA score > 90 |
| 3 | Feed loads in < 200ms with Redis cache |
| 7 | Google PageSpeed > 90 on church page |
| 10 | All settings save and reflect without restart |
| 12 | OWASP top 10 checklist passed |
