<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'الرئيسية') · إتقان</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 110'%3E%3Cpath d='M50 4 L90 18 V52 C90 78 72 96 50 106 C28 96 10 78 10 52 V18 Z' fill='%230f5e5a'/%3E%3Cpath d='M34 24 H66 V34 H44 V41 H60 V50 H44 V58 H66 V68 H34 Z' fill='white'/%3E%3Cpolyline points='43,79 48,84 57,75' fill='none' stroke='%23e0b24a' stroke-width='6' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Reem+Kufi:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/etqan.css') }}">
<style>
  /* ===== الشريط العلوي ===== */
  header.top{display:flex !important;align-items:center;gap:18px;background:#0e534f !important;color:#fff;padding:0 22px !important;height:64px;min-height:64px;
    box-shadow:0 1px 0 rgba(0,0,0,.08);position:relative;z-index:20;flex-wrap:nowrap}
  header.top a{color:inherit;text-decoration:none}
  .tb-org{display:flex;align-items:center;gap:10px;min-width:0}
  .tb-org .lg{width:44px;height:44px;border-radius:10px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;flex:none}
  .tb-org .lg img{max-width:40px;max-height:40px;object-fit:contain}
  .tb-org b{display:block;font-size:16px;line-height:1.25;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:260px}
  .tb-org span{display:block;font-size:11px;opacity:.85;direction:ltr;text-align:right;white-space:nowrap}
  .tb-nav{display:flex;gap:6px;margin-inline-start:18px}
  .tb-nav a{display:flex;align-items:center;gap:8px;padding:9px 16px;border-radius:10px;font-size:14px;font-weight:600;color:#d7ebe8 !important;transition:background .15s}
  .tb-nav a:hover{background:rgba(255,255,255,.08)}
  .tb-nav a.on{background:#1b6e68;color:#fff !important}
  .tb-sp{flex:1}
  .tb-user{position:relative}
  .tb-user summary{list-style:none;display:flex;align-items:center;gap:10px;cursor:pointer;padding:6px 8px;border-radius:12px}
  .tb-user summary::-webkit-details-marker{display:none}
  .tb-user summary:hover{background:rgba(255,255,255,.08)}
  .tb-user .av{width:42px;height:42px;border-radius:50%;border:2px solid #9fd3cc;display:flex;align-items:center;justify-content:center;background:#0b4743;flex:none}
  .tb-user .nm b{display:block;font-size:14.5px;line-height:1.3}
  .tb-user .nm span{display:block;font-size:12px;opacity:.8}
  .tb-user .menu{position:absolute;top:calc(100% + 6px);left:0;right:auto;background:#fff;color:#1c2826;border:1px solid #dde3e1;border-radius:12px;
    box-shadow:0 12px 30px rgba(14,83,79,.18);min-width:210px;padding:6px;z-index:30}
  .tb-user .menu a,.tb-user .menu button{display:flex;align-items:center;gap:10px;width:100%;padding:9px 12px;border-radius:8px;font:inherit;font-size:14px;
    color:#1c2826 !important;background:none;border:0;cursor:pointer;text-align:start}
  .tb-user .menu a:hover,.tb-user .menu button:hover{background:#eef5f4}
  .tb-user .menu .last{color:#b3261e !important}
  .tb-user .menu hr{border:0;border-top:1px solid #eef0f0;margin:4px 0}
  .tb-user .menu .last-login{font-size:11.5px;color:#5f6f6c;padding:4px 12px 6px}
  header.top .brand{display:flex;align-items:center;gap:8px;opacity:.95;flex:none}
  header.top .brand .brand-ar{font-family:"Reem Kufi","Cairo",sans-serif;font-size:18px;font-weight:700;line-height:1}
  header.top .brand .brand-en{font-size:9px;letter-spacing:2px;opacity:.75}
  header.top .brand .bt{display:flex;flex-direction:column;gap:2px}
  .tb-div{width:1px;height:30px;background:rgba(255,255,255,.18);flex:none}
  @media (max-width:980px){
    .tb-org b{max-width:150px} .tb-org span,.tb-user .nm,header.top .brand .bt{display:none} .tb-nav{margin-inline-start:6px} .tb-nav a span{display:none}
  }
  @media (max-width:560px){ header.top{gap:10px;padding:0 12px !important} .tb-div,header.top .brand{display:none !important} .tb-user .menu{position:fixed;left:8px;top:60px} .tb-org .lg{width:38px;height:38px} .tb-org b{font-size:14px;max-width:120px} }
  @media print{ header.top{display:none !important} }
</style>
@stack('head')
</head>
<body>
@php
  $me = auth()->user();
@endphp

<header class="top">
  @if ($me && $me->tenant)
    <a href="{{ route('home') }}" class="tb-org">
      <span class="lg">
        @if ($me->tenant->logoUrl())
          <img src="{{ $me->tenant->logoUrl() }}" alt="">
        @else
          <span style="color:#0e534f">{!! \App\Support\Icon::svg('store', 24) !!}</span>
        @endif
      </span>
      <div><b>{{ $me->tenant->name_ar }}</b><span>{{ $me->tenant->name_en }}</span></div>
    </a>
  @endif

  <a href="{{ route('home') }}" class="brand" title="إتقان · نظام إدارة الجودة">
    <svg width="34" height="37" viewBox="0 0 100 110" aria-hidden="true"><defs><filter id="etqsh1" x="-40%" y="-40%" width="180%" height="180%"><feDropShadow dx="0" dy="1.2" stdDeviation="1" flood-color="#000000" flood-opacity="0.35"></feDropShadow></filter></defs>
      <path d="M50 4 L90 18 V52 C90 78 72 96 50 106 C28 96 10 78 10 52 V18 Z" fill="#ffffff"></path><path d="M50 14 L81 25 V52 C81 72 67 86 50 95 C33 86 19 72 19 52 V25 Z" fill="none" stroke="#c08a1e" stroke-width="1.6"></path>
      <path d="M34 24 H66 V34 H44 V41 H60 V50 H44 V58 H66 V68 H34 Z" fill="#0f5e5a"></path><polyline points="43,79 48,84 57,75" fill="none" stroke="#c08a1e" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" filter="url(#etqsh1)"></polyline>
    </svg>
    <span class="bt"><span class="brand-ar">إتقان</span><span class="brand-en">ETQAN</span></span>
  </a>

  <nav class="tb-nav">
    <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'on' : '' }}">{!! \App\Support\Icon::svg('home', 19) !!}<span>الرئيسية</span></a>
    @if ($me && $me->isSuperAdmin())
      <a href="{{ route('admin.tenants.index') }}" class="{{ request()->routeIs('admin.tenants.*') ? 'on' : '' }}">{!! \App\Support\Icon::svg('store', 19) !!}<span>العملاء</span></a>
    @endif
    @if ($me && $me->isClientAdmin())
      <a href="{{ route('client.users.index') }}" class="{{ request()->routeIs('client.users.*') ? 'on' : '' }}">{!! \App\Support\Icon::svg('users', 19) !!}<span>المستخدمون</span></a>
    @endif
    @yield('nav')
  </nav>

  <span class="tb-sp"></span>

  @if ($me)
    <details class="tb-user">
      <summary>
        <span class="av">{!! \App\Support\Icon::svg('user', 24) !!}</span>
        <span class="nm"><b>{{ $me->displayName() }}</b><span>{{ $me->role?->name_ar }}</span></span>
        {!! \App\Support\Icon::svg('down', 16) !!}
      </summary>
      <div class="menu">
        @if ($me->last_login_at)
          <div class="last-login">آخر دخول: <span class="ltr">{{ $me->last_login_at->timezone('Africa/Cairo')->format('Y-m-d H:i') }}</span></div>
          <hr>
        @endif
        <a href="{{ route('password.edit') }}">{!! \App\Support\Icon::svg('key', 18) !!} تغيير كلمة المرور</a>
        @if ($me->isClientAdmin())
          <a href="{{ route('client.settings.edit') }}">{!! \App\Support\Icon::svg('settings', 18) !!} إعدادات المخزن</a>
        @endif
        <hr>
        <form method="POST" action="{{ route('logout') }}" style="margin:0">
          @csrf
          <button type="submit" class="last">{!! \App\Support\Icon::svg('logout', 18) !!} تسجيل الخروج</button>
        </form>
      </div>
    </details>
  @endif
</header>

<main class="wrap">
  @if (session('status'))
    <div class="alert ok">{{ session('status') }}</div>
  @endif

  @if ($errors->any())
    <div class="alert bad">
      <b>يرجى مراجعة البيانات:</b>
      <ul>
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @yield('content')
</main>

<footer class="foot">إتقان · نظام إدارة الجودة لتوزيع الأدوية</footer>
<script>
  // إغلاق قائمة المستخدم عند الضغط خارجها
  document.addEventListener('click', e => { const d = document.querySelector('.tb-user'); if (d && d.open && !d.contains(e.target)) d.open = false; });
</script>
@stack('scripts')
</body>
</html>