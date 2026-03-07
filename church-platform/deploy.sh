#!/bin/bash
# ============================================================
#  Church Platform — Deploy / Setup Script
#  Works on shared hosting (cPanel) — git is OPTIONAL
# ============================================================
#  First time:   bash deploy.sh setup
#  Git deploy:   bash deploy.sh           (pulls + migrates + caches)
#  No-git update via FTP: bash deploy.sh postupload
#  Individual:   bash deploy.sh [composer|migrate|build|cache]
# ============================================================

# Do NOT use set -e — we handle errors per-step
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$DIR"

ok()   { echo -e "${GREEN}  ✓ $1${NC}"; }
fail() { echo -e "${RED}  ✗ $1${NC}"; }
info() { echo -e "${CYAN}  → $1${NC}"; }
warn() { echo -e "${YELLOW}  ⚠ $1${NC}"; }

echo -e "${BLUE}============================================${NC}"
echo -e "${BLUE}  Church Platform — Deploy Script${NC}"
echo -e "${BLUE}============================================${NC}"
echo ""

# ── Helpers ──────────────────────────────────────────────────

has_git() {
    git -C "$DIR" rev-parse --git-dir > /dev/null 2>&1
}

php_bin() {
    # Prefer PHP 8.x on servers that have multiple versions
    for bin in php82 php81 php8.2 php8.1 php; do
        if command -v "$bin" &>/dev/null; then
            echo "$bin"
            return
        fi
    done
    echo "php"
}
PHP=$(php_bin)

ensure_env() {
    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            cp .env.example .env
            # Generate a random APP_KEY
            KEY="base64:$(head -c 32 /dev/urandom | base64 | tr -d '\n')"
            sed -i "s|^APP_KEY=.*|APP_KEY=$KEY|" .env
            ok ".env created from .env.example (edit DB credentials)"
        else
            fail ".env.example not found — cannot create .env"
            return 1
        fi
    else
        ok ".env already exists"
    fi
}

ensure_htaccess() {
    # Root .htaccess — routes public_html/ → public/
    if [ ! -f ".htaccess" ]; then
        cat > .htaccess << 'HTACCESS'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^public/ - [L]
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
<FilesMatch "^(\.env|composer\.(json|lock)|artisan|socket-server\.php)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>
HTACCESS
        ok "Root .htaccess created"
    else
        ok "Root .htaccess already exists"
    fi

    # public/.htaccess — standard Laravel rewrite
    if [ ! -f "public/.htaccess" ]; then
        cat > public/.htaccess << 'HTACCESS'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>
    RewriteEngine On
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
HTACCESS
        ok "public/.htaccess created"
    else
        ok "public/.htaccess already exists"
    fi
}

ensure_storage() {
    # Create required storage dirs if missing (can happen after zip upload)
    for d in storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache; do
        mkdir -p "$d"
    done
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
    ok "Storage directories ready"
}

# ── Steps ─────────────────────────────────────────────────────

step_pull() {
    echo -e "${YELLOW}[git] Pulling latest code...${NC}"
    if has_git; then
        BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "main")
        info "Branch: $BRANCH"
        if git pull origin "$BRANCH"; then
            ok "Code updated from git"
        else
            fail "git pull failed — continuing with local files"
        fi
    else
        warn "Not a git repository — skipping git pull"
        info "Upload files via FTP/SCP then run: bash deploy.sh postupload"
    fi
    echo ""
}

step_setup() {
    echo -e "${YELLOW}[setup] First-time setup...${NC}"
    ensure_env
    ensure_htaccess
    ensure_storage
    echo ""
}

step_composer() {
    echo -e "${YELLOW}[composer] Installing PHP dependencies...${NC}"
    if [ ! -d "vendor" ]; then
        if command -v composer &>/dev/null; then
            composer install --no-dev --optimize-autoloader --no-interaction
            ok "Composer dependencies installed"
        else
            fail "vendor/ missing and composer not found"
            info "On cPanel: use SSH or ask host to run: composer install --no-dev"
            info "Or upload a pre-built package with vendor/ included"
        fi
    else
        if command -v composer &>/dev/null; then
            composer install --no-dev --optimize-autoloader --no-interaction
            ok "Composer dependencies updated"
        else
            ok "vendor/ exists — skipping (composer not available)"
        fi
    fi
    echo ""
}

step_migrate() {
    echo -e "${YELLOW}[migrate] Running database migrations...${NC}"
    if [ ! -f ".env" ]; then
        fail ".env not found — run setup first: bash deploy.sh setup"
        return
    fi
    if $PHP artisan migrate --force 2>&1; then
        ok "Migrations complete"
    else
        fail "Migration failed — check DB credentials in .env"
    fi
    echo ""
}

step_build() {
    echo -e "${YELLOW}[build] Building frontend assets...${NC}"
    if [ -d "public/build" ]; then
        ok "public/build/ already exists — skipping (delete to force rebuild)"
    elif command -v npm &>/dev/null; then
        [ ! -d "node_modules" ] && npm install --silent
        npm run build && ok "Assets built" || fail "npm run build failed"
    else
        warn "npm not found — upload pre-built public/build/ directory"
        info "On your local machine run: npm run build, then upload public/build/"
    fi
    echo ""
}

step_cache() {
    echo -e "${YELLOW}[cache] Optimizing application...${NC}"
    $PHP artisan optimize:clear   2>/dev/null && true
    $PHP artisan config:cache     2>/dev/null && ok "Config cached"    || warn "config:cache failed"
    $PHP artisan route:cache      2>/dev/null && ok "Routes cached"    || warn "route:cache failed"
    $PHP artisan view:cache       2>/dev/null && ok "Views cached"     || warn "view:cache failed"
    if [ ! -L "public/storage" ]; then
        $PHP artisan storage:link 2>/dev/null && ok "Storage linked"   || warn "storage:link failed"
    fi
    echo ""
}

# ── Entry point ───────────────────────────────────────────────

case "${1:-all}" in
    setup)
        step_setup
        step_composer
        echo -e "${CYAN}  Next steps:${NC}"
        echo -e "  1. Edit ${YELLOW}.env${NC} — set DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD, APP_URL"
        echo -e "  2. Run ${YELLOW}bash deploy.sh migrate${NC} — create DB tables"
        echo -e "  3. Visit ${YELLOW}https://yourdomain.com/install${NC} — complete wizard"
        echo ""
        ;;
    postupload)
        # After FTP/SCP upload — no git, just setup + migrate + cache
        step_setup
        step_composer
        step_migrate
        step_cache
        echo -e "${GREEN}============================================${NC}"
        echo -e "${GREEN}  Post-upload complete ✓${NC}"
        echo -e "${GREEN}  Visit /install to complete setup${NC}"
        echo -e "${GREEN}============================================${NC}"
        ;;
    pull)
        step_pull
        ;;
    composer)
        step_composer
        ;;
    migrate)
        step_migrate
        ;;
    build)
        step_build
        ;;
    cache)
        step_cache
        ;;
    all|"")
        step_pull
        step_setup
        step_composer
        step_migrate
        step_build
        step_cache
        echo -e "${GREEN}============================================${NC}"
        echo -e "${GREEN}  Deploy complete ✓${NC}"
        echo -e "${GREEN}============================================${NC}"
        ;;
    *)
        echo "Usage: bash deploy.sh [command]"
        echo ""
        echo "  (none)      Full deploy (git pull + all steps)"
        echo "  setup       First-time: create .env, .htaccess, storage dirs"
        echo "  postupload  After FTP upload: setup + migrate + cache (no git)"
        echo "  pull        Git pull only"
        echo "  composer    Install PHP dependencies"
        echo "  migrate     Run database migrations"
        echo "  build       Build frontend assets (npm run build)"
        echo "  cache       Clear and rebuild Laravel caches"
        echo ""
        exit 1
        ;;
esac
