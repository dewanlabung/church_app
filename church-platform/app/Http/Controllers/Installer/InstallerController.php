<?php

namespace App\Http\Controllers\Installer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class InstallerController extends Controller
{
    // ── Step 1: Requirements check ───────────────────────────────────────────

    public function welcome()
    {
        $php = [
            'php_version' => ['label' => 'PHP >= 8.1', 'ok' => version_compare(PHP_VERSION, '8.1.0', '>='), 'value' => PHP_VERSION],
        ];

        $extensions = collect([
            'pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer',
            'json', 'curl', 'fileinfo', 'gd', 'xml', 'bcmath',
        ])->mapWithKeys(fn ($ext) => [$ext => ['label' => $ext, 'ok' => extension_loaded($ext)]]);

        $permissions = collect([
            'storage'           => storage_path(),
            'storage/framework' => storage_path('framework'),
            'storage/logs'      => storage_path('logs'),
            'bootstrap/cache'   => base_path('bootstrap/cache'),
        ])->mapWithKeys(fn ($path, $label) => [$label => ['label' => $label, 'ok' => is_writable($path), 'path' => $path]]);

        $extras = [
            'vendor_exists' => ['label' => 'vendor/ directory', 'ok' => is_dir(base_path('vendor')), 'note' => 'Run: composer install --no-dev'],
            'assets_built'  => ['label' => 'public/build/ (frontend)', 'ok' => is_dir(public_path('build')), 'note' => 'Run: npm run build'],
        ];

        $allOk = !collect($php)->pluck('ok')->contains(false)
              && !$extensions->pluck('ok')->contains(false)
              && !collect($permissions)->pluck('ok')->contains(false)
              && $extras['vendor_exists']['ok'];

        return view('installer.welcome', compact('php', 'extensions', 'permissions', 'extras', 'allOk'));
    }

    // ── Step 2: Database config ──────────────────────────────────────────────

    public function database()
    {
        if (!session('requirements_passed')) {
            return redirect('/install');
        }
        return view('installer.database');
    }

    /** AJAX or POST — test connection without saving */
    public function testDatabase(Request $request)
    {
        $request->validate([
            'db_host'     => 'required|string',
            'db_port'     => 'required|integer',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        try {
            $dsn = "mysql:host={$request->db_host};port={$request->db_port};dbname={$request->db_database};charset=utf8mb4";
            $pdo = new \PDO($dsn, $request->db_username, $request->db_password ?? '');
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
            return response()->json(['ok' => true, 'message' => "Connected — MySQL {$ver}"]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /** Save DB info to .env and proceed to step 3 */
    public function saveDatabase(Request $request)
    {
        $request->validate([
            'db_host'     => 'required|string',
            'db_port'     => 'required|integer',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
            'app_url'     => 'required|url',
        ]);

        // Test connection first
        try {
            $dsn = "mysql:host={$request->db_host};port={$request->db_port};dbname={$request->db_database};charset=utf8mb4";
            $pdo = new \PDO($dsn, $request->db_username, $request->db_password ?? '');
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\Throwable $e) {
            return back()->withErrors(['db_host' => 'Connection failed: ' . $e->getMessage()])->withInput();
        }

        $this->writeEnv([
            'APP_URL'     => rtrim($request->app_url, '/'),
            'DB_HOST'     => $request->db_host,
            'DB_PORT'     => $request->db_port,
            'DB_DATABASE' => $request->db_database,
            'DB_USERNAME' => $request->db_username,
            'DB_PASSWORD' => $request->db_password ?? '',
        ]);

        // Reconnect Laravel's DB with new credentials
        config([
            'database.connections.mysql.host'     => $request->db_host,
            'database.connections.mysql.port'     => $request->db_port,
            'database.connections.mysql.database' => $request->db_database,
            'database.connections.mysql.username' => $request->db_username,
            'database.connections.mysql.password' => $request->db_password ?? '',
        ]);
        DB::purge('mysql');

        session(['db_configured' => true]);

        return redirect('/install/admin');
    }

    // ── Step 3: Admin + site info ────────────────────────────────────────────

    public function admin()
    {
        if (!session('db_configured')) {
            return redirect('/install/database');
        }
        return view('installer.admin');
    }

    /** Run the full installation */
    public function install(Request $request)
    {
        if (!session('db_configured')) {
            return response()->json(['ok' => false, 'message' => 'Database not configured.'], 422);
        }

        $request->validate([
            'site_name'   => 'required|string|max:255',
            'admin_name'  => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_pass'  => 'required|min:8|confirmed',
        ]);

        try {
            // 1. Write site name to .env
            $this->writeEnv(['APP_NAME' => $request->site_name]);

            // 2. Generate APP_KEY if blank
            Artisan::call('key:generate', ['--force' => true]);
            Artisan::call('config:clear');

            // 3. Run migrations
            Artisan::call('migrate', ['--force' => true]);

            // 4. Seed roles/permissions (if seeder exists)
            try {
                Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder', '--force' => true]);
            } catch (\Throwable) {
                // seeder optional
            }

            // 5. Create admin user
            $userModel = app(\App\Models\User::class);
            $admin = $userModel::create([
                'name'              => $request->admin_name,
                'email'             => $request->admin_email,
                'password'          => Hash::make($request->admin_pass),
                'user_type'         => 'super_admin',
                'email_verified_at' => now(),
            ]);

            // Assign super_admin role if Spatie is set up
            try { $admin->assignRole('super_admin'); } catch (\Throwable) {}

            // 6. Create Setting record if model exists
            try {
                if (class_exists(\App\Models\Setting::class)) {
                    \App\Models\Setting::firstOrCreate([], [
                        'church_name' => $request->site_name,
                    ]);
                }
            } catch (\Throwable) {}

            // 7. Storage link
            try { Artisan::call('storage:link'); } catch (\Throwable) {}

            // 8. Write .htaccess files
            $this->writeHtaccess();

            // 9. Cache config for production
            try {
                Artisan::call('config:cache');
                Artisan::call('route:cache');
            } catch (\Throwable) {}

            // 10. Mark installed
            File::put(storage_path('installed'), now()->toDateTimeString());

            return response()->json(['ok' => true, 'redirect' => '/']);

        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Write/update key=value pairs in .env */
    private function writeEnv(array $data): void
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            $example = base_path('.env.example');
            File::copy(File::exists($example) ? $example : '/dev/null', $envPath);
        }

        $content = File::get($envPath);

        foreach ($data as $key => $value) {
            // Quote values with spaces or special chars
            $safe = preg_match('/[\s#"\'\\\\]/', (string) $value)
                ? '"' . addslashes((string) $value) . '"'
                : (string) $value;

            if (preg_match("/^{$key}=/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$safe}", $content);
            } else {
                $content .= "\n{$key}={$safe}";
            }
        }

        File::put($envPath, $content);
    }

    /** Auto-generate both .htaccess files for shared hosting */
    private function writeHtaccess(): void
    {
        // Root .htaccess — redirects everything to public/
        $rootHtaccess = base_path('.htaccess');
        if (!File::exists($rootHtaccess)) {
            File::put($rootHtaccess, <<<'HTACCESS'
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Redirect all requests to public/
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ /public/$1 [L]
</IfModule>

# Deny access to sensitive files
<FilesMatch "(^\.env|composer\.json|composer\.lock|artisan|socket-server\.php)$">
    Order allow,deny
    Deny from all
</FilesMatch>
HTACCESS);
        }

        // public/.htaccess — standard Laravel rewrite
        $publicHtaccess = public_path('.htaccess');
        if (!File::exists($publicHtaccess)) {
            File::put($publicHtaccess, <<<'HTACCESS'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
HTACCESS);
        }
    }
}
