@extends('installer.layout')
@php $currentStep = 3; @endphp

@section('content')
<div style="padding: 36px 32px;">
    <div style="text-align:center; margin-bottom:28px;">
        <div style="font-size:22px; font-weight:700; color:#1e293b; margin-bottom:6px;">Admin Account &amp; Site Info</div>
        <p style="color:#64748b; font-size:14px;">Almost there — create your super admin account</p>
    </div>

    {{-- Progress bar (visual only) --}}
    <div style="height:4px; background:#e2e8f0; border-radius:99px; margin-bottom:28px; overflow:hidden;">
        <div id="progressBar" style="height:100%; width:0%; background:linear-gradient(90deg,#4f46e5,#7c3aed); border-radius:99px; transition:width .4s;"></div>
    </div>

    {{-- Install form --}}
    <div id="installForm">
        <div class="section-title">Site Information</div>

        <div class="field">
            <label for="site_name">Site / Church Name</label>
            <input type="text" id="site_name" name="site_name" class="input" required
                placeholder="Grace Community Church" autocomplete="organization">
        </div>

        <div class="section-title" style="margin-top:20px;">Super Admin Account</div>

        <div class="field">
            <label for="admin_name">Full Name</label>
            <input type="text" id="admin_name" name="admin_name" class="input" required
                placeholder="John Pastor" autocomplete="name">
        </div>

        <div class="field">
            <label for="admin_email">Email Address</label>
            <input type="email" id="admin_email" name="admin_email" class="input" required
                placeholder="admin@yourchurch.com" autocomplete="email">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;" class="field">
            <div>
                <label for="admin_pass">Password</label>
                <input type="password" id="admin_pass" name="admin_pass" class="input" required
                    placeholder="Min 8 characters" autocomplete="new-password">
            </div>
            <div>
                <label for="admin_pass_confirmation">Confirm Password</label>
                <input type="password" id="admin_pass_confirmation" name="admin_pass_confirmation" class="input" required
                    placeholder="Repeat password" autocomplete="new-password">
            </div>
        </div>

        <div id="formError" style="display:none;" class="alert-err"></div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px;">
            <a href="/install/database" class="btn-secondary">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Back
            </a>
            <button type="button" onclick="runInstall()" class="btn-primary" id="installBtn">
                Install Now 🚀
            </button>
        </div>
    </div>

    {{-- Success state (hidden until install completes) --}}
    <div id="successPanel" style="display:none; text-align:center; padding: 20px 0;">
        <div style="font-size:56px; margin-bottom:16px;">🎉</div>
        <div style="font-size:22px; font-weight:700; color:#166534; margin-bottom:8px;">Installation Complete!</div>
        <p style="color:#64748b; font-size:14px; margin-bottom:28px;">Your church platform is ready. Welcome aboard!</p>
        <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
            <a href="/" class="btn-primary">
                Visit Your Site
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
            <a href="/admin" class="btn-secondary">
                Go to Admin Panel
            </a>
        </div>
        <div style="margin-top:24px; padding:14px 16px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; font-size:13px; color:#166534; text-align:left;">
            <strong>✅ What was set up:</strong>
            <ul style="margin-top:8px; padding-left:18px; line-height:2;">
                <li>Database tables created (migrations run)</li>
                <li>Super admin account created</li>
                <li>APP_KEY generated &amp; .env written</li>
                <li>.htaccess files auto-generated</li>
                <li>Config cached for production</li>
            </ul>
        </div>
        <div style="margin-top:12px; padding:12px 16px; background:#fff7ed; border:1px solid #fed7aa; border-radius:12px; font-size:12px; color:#9a3412;">
            ⚠️ For security, the installer is now locked. If you need to re-run it, delete <code>storage/installed</code>.
        </div>
    </div>

    {{-- Installing state --}}
    <div id="installingPanel" style="display:none; text-align:center; padding:20px 0;">
        <div style="font-size:40px; margin-bottom:16px;">⚙️</div>
        <div style="font-size:18px; font-weight:600; color:#1e293b; margin-bottom:8px;" id="installStatus">Running migrations…</div>
        <p style="color:#94a3b8; font-size:13px;">This may take a moment. Please don't refresh.</p>
        <div style="width:200px; height:4px; background:#e2e8f0; border-radius:99px; margin:20px auto; overflow:hidden;">
            <div style="height:100%; background:linear-gradient(90deg,#4f46e5,#7c3aed); border-radius:99px; animation:loading 1.5s infinite ease-in-out; transform-origin:left;"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
async function runInstall() {
    const fields = ['site_name', 'admin_name', 'admin_email', 'admin_pass', 'admin_pass_confirmation'];
    const data = Object.fromEntries(fields.map(id => [id, document.getElementById(id).value]));

    // Client-side validation
    if (!data.site_name || !data.admin_name || !data.admin_email || !data.admin_pass) {
        showError('Please fill in all required fields.');
        return;
    }
    if (data.admin_pass !== data.admin_pass_confirmation) {
        showError('Passwords do not match.');
        return;
    }
    if (data.admin_pass.length < 8) {
        showError('Password must be at least 8 characters.');
        return;
    }

    // Show installing state
    document.getElementById('installForm').style.display = 'none';
    document.getElementById('installingPanel').style.display = 'block';

    const steps = [
        'Generating APP_KEY…',
        'Running database migrations…',
        'Creating admin account…',
        'Writing .htaccess files…',
        'Caching configuration…',
    ];
    let si = 0;
    const statusEl = document.getElementById('installStatus');
    const interval = setInterval(() => {
        if (si < steps.length - 1) statusEl.textContent = steps[++si];
    }, 1200);

    try {
        const resp = await fetch('/install/admin', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify(data),
        });
        clearInterval(interval);
        const json = await resp.json();

        if (json.ok) {
            document.getElementById('installingPanel').style.display = 'none';
            document.getElementById('successPanel').style.display = 'block';
        } else {
            document.getElementById('installingPanel').style.display = 'none';
            document.getElementById('installForm').style.display = 'block';
            showError(json.message ?? 'Installation failed. Please try again.');
        }
    } catch (e) {
        clearInterval(interval);
        document.getElementById('installingPanel').style.display = 'none';
        document.getElementById('installForm').style.display = 'block';
        showError('Network error: ' + e.message);
    }
}

function showError(msg) {
    const el = document.getElementById('formError');
    el.style.display = 'block';
    el.textContent = msg;
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>
<style>
@keyframes loading {
    0%  { transform: scaleX(0); margin-left: 0; }
    50% { transform: scaleX(1); margin-left: 0; }
    100%{ transform: scaleX(0); margin-left: 100%; }
}
</style>
@endpush
