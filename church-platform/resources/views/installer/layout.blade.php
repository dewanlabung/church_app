<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Church Platform — Installer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: linear-gradient(135deg, #f0f4ff 0%, #e8eeff 50%, #f4f0ff 100%);
            min-height: 100vh;
            color: #1e293b;
        }
        .card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 60px -15px rgba(79,70,229,.13), 0 4px 20px rgba(0,0,0,.06);
        }
        .btn-primary {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 11px 24px; background: #4f46e5; color: #fff; border: none;
            border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer;
            transition: background .15s, transform .1s, box-shadow .15s;
            text-decoration: none;
        }
        .btn-primary:hover { background: #4338ca; box-shadow: 0 4px 14px rgba(79,70,229,.3); transform: translateY(-1px); }
        .btn-primary:active { transform: translateY(0); }
        .btn-primary:disabled { opacity: .6; cursor: not-allowed; transform: none; }
        .btn-secondary {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 10px 20px; background: #f1f5f9; color: #475569; border: none;
            border-radius: 10px; font-size: 14px; font-weight: 500; cursor: pointer;
            text-decoration: none; transition: background .15s;
        }
        .btn-secondary:hover { background: #e2e8f0; }
        .input {
            width: 100%; padding: 11px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px;
            font-size: 14px; font-family: inherit; color: #1e293b; background: #f8fafc;
            transition: border-color .15s, box-shadow .15s; outline: none;
        }
        .input:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,.12); background: #fff; }
        .input.error { border-color: #ef4444; }
        label { display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px; }
        .field { margin-bottom: 16px; }
        .field:last-child { margin-bottom: 0; }
        .alert-err { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 12px 16px; border-radius: 10px; font-size: 14px; margin-bottom: 16px; }
        .badge-ok  { display: inline-flex; align-items: center; gap: 4px; background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; padding: 3px 10px; border-radius: 99px; font-size: 12px; font-weight: 600; }
        .badge-err { display: inline-flex; align-items: center; gap: 4px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 3px 10px; border-radius: 99px; font-size: 12px; font-weight: 600; }
        .badge-warn{ display: inline-flex; align-items: center; gap: 4px; background: #fffbeb; color: #d97706; border: 1px solid #fde68a; padding: 3px 10px; border-radius: 99px; font-size: 12px; font-weight: 600; }
        .check-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 10px; margin-bottom: 6px; background: #f8fafc; }
        .check-row.ok  { background: #f0fdf4; }
        .check-row.err { background: #fef2f2; }
        .check-row.warn{ background: #fffbeb; }
        .step-bar { display: flex; align-items: center; gap: 0; }
        .step-node { display: flex; flex-direction: column; align-items: center; gap: 6px; flex: 0 0 auto; }
        .step-circle {
            width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 700; transition: all .3s;
        }
        .step-circle.done  { background: #22c55e; color: #fff; box-shadow: 0 0 0 4px rgba(34,197,94,.18); }
        .step-circle.active{ background: #4f46e5; color: #fff; box-shadow: 0 0 0 4px rgba(79,70,229,.18); animation: pulse 2s infinite; }
        .step-circle.future{ background: #fff; color: #94a3b8; border: 2px solid #e2e8f0; }
        .step-line { flex: 1; height: 2px; background: #e2e8f0; transition: background .4s; }
        .step-line.done { background: #22c55e; }
        .step-label { font-size: 11px; font-weight: 600; text-align: center; white-space: nowrap; }
        @keyframes pulse { 0%,100%{box-shadow:0 0 0 0 rgba(79,70,229,.4)} 50%{box-shadow:0 0 0 8px rgba(79,70,229,0)} }
        @keyframes fadeUp { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }
        .fade-up { animation: fadeUp .5s ease-out; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-bottom: 10px; }
        @media(max-width: 480px) { .step-label { display: none; } }
    </style>
    @stack('styles')
</head>
<body>
<div style="min-height:100vh; display:flex; flex-direction:column;">

    {{-- Header --}}
    <header style="padding: 32px 16px 20px; text-align: center;">
        <div style="display:inline-flex; align-items:center; justify-content:center; width:56px; height:56px; background:#fff; border-radius:16px; box-shadow:0 4px 20px rgba(79,70,229,.15); margin-bottom:12px;">
            <svg width="28" height="28" fill="none" stroke="#4f46e5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M12 2L12 10M12 10H6M12 10H18M8 6H16"/>
                <path d="M4 22V12l8-4 8 4v10"/>
            </svg>
        </div>
        <div style="font-size:20px; font-weight:700; color:#1e293b;">Church Platform</div>
        <div style="font-size:13px; color:#6366f1; font-weight:500; margin-top:2px;">Installation Wizard</div>
    </header>

    {{-- Step indicator --}}
    @php
        $steps = ['Requirements', 'Database', 'Admin Account'];
        $cur = $currentStep ?? 1;
    @endphp
    <div style="max-width: 480px; margin: 0 auto 28px; padding: 0 20px; width:100%;">
        <div class="step-bar">
            @foreach($steps as $i => $label)
                @php $n = $i + 1; @endphp
                <div class="step-node">
                    <div class="step-circle {{ $n < $cur ? 'done' : ($n === $cur ? 'active' : 'future') }}">
                        @if($n < $cur)
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                        @else
                            {{ $n }}
                        @endif
                    </div>
                    <span class="step-label" style="color: {{ $n === $cur ? '#4f46e5' : ($n < $cur ? '#22c55e' : '#94a3b8') }}">{{ $label }}</span>
                </div>
                @if(!$loop->last)
                    <div class="step-line {{ $n < $cur ? 'done' : '' }}" style="margin-bottom: 20px;"></div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Content --}}
    <main style="flex:1; padding: 0 16px 48px;">
        <div style="max-width: 560px; margin: 0 auto;">
            <div class="card fade-up">
                @yield('content')
            </div>
        </div>
    </main>

    <footer style="padding: 20px; text-align:center; font-size:12px; color:#94a3b8;">
        Church Platform &copy; {{ date('Y') }}
    </footer>
</div>
@stack('scripts')
</body>
</html>
