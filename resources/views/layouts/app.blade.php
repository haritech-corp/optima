<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'OPTIMA ERP')</title>
    <style>
        :root{--navy:#17324d;--blue:#2c6eaa;--ink:#17212b;--muted:#667788;--line:#dce4eb;--bg:#f5f7fa;--white:#fff;--danger:#b42318;--success:#067647;--warning:#b54708}*{box-sizing:border-box}body{margin:0;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:var(--bg);color:var(--ink)}a{color:inherit;text-decoration:none}.shell{display:grid;grid-template-columns:248px 1fr;min-height:100vh}.sidebar{background:var(--navy);color:#fff;padding:28px 20px;position:sticky;top:0;height:100vh}.brand{font-size:24px;font-weight:800;letter-spacing:.08em;margin-bottom:6px}.brand-sub{font-size:12px;color:#b9c8d6;margin-bottom:36px}.nav a{display:block;padding:11px 14px;margin:5px 0;border-radius:9px;color:#dce8f2}.nav a:hover,.nav a.active{background:#294b69;color:#fff}.sidebar-user{position:absolute;bottom:24px;left:20px;right:20px;border-top:1px solid #36536d;padding-top:16px;font-size:13px}.main{padding:28px 34px}.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.eyebrow{color:var(--blue);font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.08em}.title{font-size:28px;font-weight:800;margin:3px 0}.subtitle{color:var(--muted);font-size:14px}.grid{display:grid;gap:16px}.stats{grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:22px}.card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px;box-shadow:0 2px 8px rgba(23,50,77,.04)}.stat-label{font-size:12px;color:var(--muted);text-transform:uppercase;font-weight:700}.stat-value{font-size:30px;font-weight:800;color:var(--navy);margin-top:8px}.split{grid-template-columns:1.25fr .75fr}.btn{display:inline-flex;align-items:center;gap:7px;border:0;border-radius:9px;padding:10px 14px;font-weight:700;cursor:pointer;background:var(--navy);color:#fff}.btn.secondary{background:#fff;color:var(--navy);border:1px solid var(--line)}.btn.danger{background:#fff;color:var(--danger);border:1px solid #f1c0bd}.btn.small{padding:7px 10px;font-size:12px}.table-wrap{overflow:auto;background:#fff;border:1px solid var(--line);border-radius:14px}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:13px 14px;border-bottom:1px solid var(--line);font-size:13px}th{color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:.04em;background:#fbfcfd}.badge{display:inline-block;padding:5px 9px;border-radius:999px;background:#eaf1f7;color:var(--navy);font-size:11px;font-weight:700}.badge.overdue{background:#fee4e2;color:var(--danger)}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.field.full{grid-column:1/-1}label{display:block;font-size:12px;font-weight:700;margin-bottom:7px}input,select,textarea{width:100%;border:1px solid #cbd6df;border-radius:9px;padding:11px 12px;background:#fff;color:var(--ink)}textarea{min-height:110px;resize:vertical}.actions{display:flex;gap:9px;align-items:center;flex-wrap:wrap}.alert{padding:12px 14px;border-radius:10px;margin-bottom:16px;background:#ecfdf3;color:var(--success);border:1px solid #abefc6}.alert.error{background:#fef3f2;color:var(--danger);border-color:#fecdca}.alert.warning{background:#fffaeb;color:var(--warning);border-color:#fedf89}.empty{padding:40px;text-align:center;color:var(--muted)}.timeline{border-left:2px solid var(--line);padding-left:18px}.timeline-item{position:relative;margin-bottom:18px}.timeline-item:before{content:"";position:absolute;width:10px;height:10px;border-radius:50%;background:var(--blue);left:-24px;top:5px}.meta{font-size:12px;color:var(--muted)}.login-page{min-height:100vh;display:grid;grid-template-columns:1.1fr .9fr}.login-hero{background:linear-gradient(135deg,#102c46,#2c6eaa);color:#fff;padding:9vw;display:flex;flex-direction:column;justify-content:center}.login-card{display:flex;align-items:center;justify-content:center;padding:40px}.login-box{width:min(420px,100%)}.login-box h1{font-size:32px;margin:0 0 8px}.login-box form{margin-top:28px}.pagination{margin-top:16px}@media(max-width:900px){.shell{grid-template-columns:1fr}.sidebar{position:static;height:auto}.sidebar-user{position:static;margin-top:24px}.stats,.split{grid-template-columns:1fr 1fr}.main{padding:22px}.login-page{grid-template-columns:1fr}.login-hero{display:none}}@media(max-width:600px){.stats,.split,.form-grid{grid-template-columns:1fr}.field.full{grid-column:auto}.topbar{align-items:flex-start;gap:12px;flex-direction:column}}
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand">OPTIMA</div><div class="brand-sub">Enterprise Relationship System</div>
        <nav class="nav">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('leads.index') }}" class="{{ request()->routeIs('leads.*') ? 'active' : '' }}">CRM Leads</a>
            <a href="{{ route('clients.index') }}" class="{{ request()->routeIs('clients.*') ? 'active' : '' }}">Clients</a>
            <a href="{{ route('follow-ups.index') }}" class="{{ request()->routeIs('follow-ups.*') ? 'active' : '' }}">Follow-up Center</a>
        </nav>
        <div class="sidebar-user"><strong>{{ data_get(session('optima_user'), 'name') }}</strong><br><span style="color:#b9c8d6">{{ data_get(session('optima_user'), 'role') }}</span><form method="post" action="{{ route('logout') }}" style="margin-top:10px">@csrf<button class="btn small secondary">Keluar</button></form></div>
    </aside>
    <main class="main">
        @if(session('status'))<div class="alert">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif
        @yield('content')
    </main>
</div>
</body>
</html>
