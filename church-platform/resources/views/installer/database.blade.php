@extends('installer.layout')
@php $currentStep = 2; @endphp

@section('content')
<div style="padding: 36px 32px;">
    <div style="text-align:center; margin-bottom:28px;">
        <div style="font-size:22px; font-weight:700; color:#1e293b; margin-bottom:6px;">Database Configuration</div>
        <p style="color:#64748b; font-size:14px;">Enter your MySQL database credentials</p>
    </div>

    @if($errors->any())
        <div class="alert-err">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="/install/database" id="dbForm">
        @csrf

        {{-- Site URL --}}
        <div style="background:#f0f4ff; border:1px solid #c7d2fe; border-radius:12px; padding:16px 18px; margin-bottom:24px;">
            <div class="field" style="margin-bottom:0;">
                <label for="app_url">Site URL</label>
                <input type="url" id="app_url" name="app_url" class="input" required
                    value="{{ old('app_url', rtrim(request()->getSchemeAndHttpHost(), '/')) }}"
                    placeholder="https://yourdomain.com">
                <div style="font-size:12px; color:#6366f1; margin-top:5px;">The full URL where this site will be accessed (no trailing slash)</div>
            </div>
        </div>

        {{-- DB credentials --}}
        <div class="section-title">MySQL Database</div>

        <div style="display:grid; grid-template-columns:3fr 1fr; gap:12px;" class="field">
            <div>
                <label for="db_host">Host</label>
                <input type="text" id="db_host" name="db_host" class="input" required
                    value="{{ old('db_host', 'localhost') }}" placeholder="localhost">
            </div>
            <div>
                <label for="db_port">Port</label>
                <input type="number" id="db_port" name="db_port" class="input" required
                    value="{{ old('db_port', 3306) }}" placeholder="3306">
            </div>
        </div>

        <div class="field">
            <label for="db_database">Database Name</label>
            <input type="text" id="db_database" name="db_database" class="input" required
                value="{{ old('db_database') }}" placeholder="churchapp_db">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;" class="field">
            <div>
                <label for="db_username">Username</label>
                <input type="text" id="db_username" name="db_username" class="input" required
                    value="{{ old('db_username') }}" placeholder="db_user">
            </div>
            <div>
                <label for="db_password">Password</label>
                <input type="password" id="db_password" name="db_password" class="input"
                    value="{{ old('db_password') }}" placeholder="(leave blank if none)">
            </div>
        </div>

        {{-- Test connection button --}}
        <div style="margin-bottom: 24px;">
            <button type="button" id="testBtn" onclick="testConnection()"
                style="display:inline-flex; align-items:center; gap:7px; padding:9px 18px; background:#f8fafc; color:#475569; border:1.5px solid #e2e8f0; border-radius:9px; font-size:14px; font-weight:600; cursor:pointer; transition:all .15s;">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                Test Connection
            </button>
            <span id="testResult" style="margin-left:12px; font-size:13px; font-weight:600;"></span>
        </div>

        {{-- cPanel tip --}}
        <details style="margin-bottom:24px;">
            <summary style="font-size:13px; font-weight:600; color:#6366f1; cursor:pointer; user-select:none;">💡 cPanel shared hosting tips</summary>
            <div style="margin-top:12px; padding:14px 16px; background:#f8fafc; border-radius:10px; font-size:13px; color:#475569; line-height:1.7;">
                <strong>Finding your DB credentials in cPanel:</strong><br>
                1. Login to cPanel → <em>MySQL Databases</em><br>
                2. Create a database, create a user, add user to database with ALL PRIVILEGES<br>
                3. Username format: <code>cpaneluser_dbname</code><br>
                4. Host is almost always <code>localhost</code><br><br>
                <strong>Note:</strong> Your cPanel username prefix is added automatically to DB names and usernames.
            </div>
        </details>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px;">
            <a href="/install" class="btn-secondary">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Back
            </a>
            <button type="submit" class="btn-primary" id="submitBtn">
                Save &amp; Continue
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
async function testConnection() {
    const btn = document.getElementById('testBtn');
    const result = document.getElementById('testResult');
    const data = {
        _token: document.querySelector('[name=_token]').value,
        db_host: document.getElementById('db_host').value,
        db_port: document.getElementById('db_port').value,
        db_database: document.getElementById('db_database').value,
        db_username: document.getElementById('db_username').value,
        db_password: document.getElementById('db_password').value,
    };

    btn.disabled = true;
    btn.innerHTML = '<svg width="15" height="15" style="animation:spin 1s linear infinite" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Testing…';
    result.textContent = '';

    try {
        const resp = await fetch('/install/database/test', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': data._token },
            body: JSON.stringify(data),
        });
        const json = await resp.json();
        if (json.ok) {
            result.style.color = '#16a34a';
            result.textContent = '✓ ' + json.message;
        } else {
            result.style.color = '#dc2626';
            result.textContent = '✗ ' + json.message;
        }
    } catch (e) {
        result.style.color = '#dc2626';
        result.textContent = '✗ Network error';
    }

    btn.disabled = false;
    btn.innerHTML = '<svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg> Test Connection';
}
</script>
<style>@keyframes spin { to { transform: rotate(360deg); } }</style>
@endpush
