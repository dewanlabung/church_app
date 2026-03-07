@extends('installer.layout')
@php $currentStep = 1; @endphp

@section('content')
<div style="padding: 36px 32px;">
    <div style="text-align:center; margin-bottom:28px;">
        <div style="font-size:22px; font-weight:700; color:#1e293b; margin-bottom:6px;">Server Requirements</div>
        <p style="color:#64748b; font-size:14px;">Checking your server before we start</p>
    </div>

    {{-- PHP Version --}}
    <div class="section-title">PHP</div>
    @foreach($php as $key => $item)
        <div class="check-row {{ $item['ok'] ? 'ok' : 'err' }}">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="font-size:13px; font-weight:600; color:{{ $item['ok'] ? '#166534' : '#991b1b' }}">
                    PHP {{ $item['value'] }}
                </div>
                <span style="font-size:12px; color:#94a3b8;">Required: &gt;= 8.1</span>
            </div>
            @if($item['ok'])
                <span class="badge-ok">✓ Passed</span>
            @else
                <span class="badge-err">✗ Upgrade PHP</span>
            @endif
        </div>
    @endforeach

    {{-- Extensions --}}
    <div class="section-title" style="margin-top:20px;">PHP Extensions</div>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px;">
        @foreach($extensions as $name => $item)
            <div class="check-row {{ $item['ok'] ? 'ok' : 'err' }}" style="margin-bottom:0;">
                <span style="font-size:13px; font-weight:500; color:{{ $item['ok'] ? '#166534' : '#991b1b' }}">{{ $name }}</span>
                @if($item['ok'])
                    <svg width="16" height="16" fill="none" stroke="#22c55e" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @else
                    <svg width="16" height="16" fill="none" stroke="#ef4444" stroke-width="2.5" viewBox="0 0 24 24"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Permissions --}}
    <div class="section-title" style="margin-top:20px;">Folder Permissions (must be writable)</div>
    @foreach($permissions as $label => $item)
        <div class="check-row {{ $item['ok'] ? 'ok' : 'err' }}">
            <div>
                <span style="font-size:13px; font-weight:600; color:{{ $item['ok'] ? '#166534' : '#991b1b' }}">{{ $label }}</span>
                <div style="font-size:11px; color:#94a3b8; font-family:monospace;">{{ $item['path'] }}</div>
            </div>
            @if($item['ok'])
                <span class="badge-ok">✓ Writable</span>
            @else
                <span class="badge-err">✗ Not writable</span>
            @endif
        </div>
    @endforeach

    {{-- Extras: vendor & build --}}
    <div class="section-title" style="margin-top:20px;">Dependencies</div>
    @foreach($extras as $key => $item)
        <div class="check-row {{ $item['ok'] ? 'ok' : 'warn' }}">
            <div>
                <span style="font-size:13px; font-weight:600; color:{{ $item['ok'] ? '#166534' : '#92400e' }}">{{ $item['label'] }}</span>
                @if(!$item['ok'])
                    <div style="font-size:12px; color:#b45309; margin-top:2px;">{{ $item['note'] }}</div>
                @endif
            </div>
            @if($item['ok'])
                <span class="badge-ok">✓ Found</span>
            @else
                <span class="badge-warn">⚠ Missing</span>
            @endif
        </div>
    @endforeach

    {{-- Dependency help box --}}
    @if(!$extras['vendor_exists']['ok'])
    <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:12px; padding:16px 18px; margin-top:16px;">
        <div style="font-size:13px; font-weight:700; color:#92400e; margin-bottom:8px;">📦 Install PHP dependencies first</div>
        <p style="font-size:13px; color:#78350f; margin-bottom:10px;">SSH into your server and run:</p>
        <div style="background:#1e293b; border-radius:8px; padding:12px 14px; font-family:monospace; font-size:13px; color:#86efac; overflow-x:auto;">
            cd /home/yourusername/public_html<br>
            composer install --no-dev --optimize-autoloader
        </div>
        <p style="font-size:12px; color:#92400e; margin-top:10px;">No SSH? Contact your host to run Composer, or upload a pre-built package with <code>vendor/</code> included.</p>
    </div>
    @endif

    @if(!$extras['assets_built']['ok'])
    <div style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:12px; padding:16px 18px; margin-top:12px;">
        <div style="font-size:13px; font-weight:700; color:#0c4a6e; margin-bottom:8px;">🎨 Frontend assets not built</div>
        <div style="background:#1e293b; border-radius:8px; padding:12px 14px; font-family:monospace; font-size:13px; color:#86efac; overflow-x:auto;">
            npm install && npm run build
        </div>
        <p style="font-size:12px; color:#0369a1; margin-top:8px;">Or upload a package that already has <code>public/build/</code> included.</p>
    </div>
    @endif

    {{-- CTA --}}
    <div style="margin-top:28px; display:flex; justify-content:flex-end;">
        @if($allOk || $extras['vendor_exists']['ok'])
            <form method="POST" action="/install/proceed">
                @csrf
                <button type="submit" class="btn-primary">
                    Continue to Database
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
            </form>
        @else
            <button class="btn-primary" disabled style="opacity:.5; cursor:not-allowed;">
                Fix issues above first
            </button>
        @endif
    </div>
</div>
@endsection
