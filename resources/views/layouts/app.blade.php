<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'الرئيسية') · إتقان</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 110'%3E%3Cpath d='M50 4 L90 18 V52 C90 78 72 96 50 106 C28 96 10 78 10 52 V18 Z' fill='%230f5e5a'/%3E%3Cpath d='M34 24 H66 V34 H44 V41 H60 V50 H44 V58 H66 V68 H34 Z' fill='white'/%3E%3Cpolyline points='43,79 48,84 57,75' fill='none' stroke='%23e0b24a' stroke-width='6' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E">
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#0e534f">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="إتقان">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="apple-touch-icon" href="/icons/etqan-180.png">
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
  /* ===== الجرس ===== */
  .tb-bell{position:relative}
  .tb-bell summary{list-style:none;cursor:pointer;width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;position:relative}
  .tb-bell summary::-webkit-details-marker{display:none}
  .tb-bell summary:hover,.tb-bell[open] summary{background:rgba(255,255,255,.1)}
  .tb-bell .nb-n{position:absolute;top:3px;left:2px;min-width:19px;height:19px;padding:0 5px;border-radius:99px;background:#d93025;color:#fff;font-size:11px;font-weight:700;
    display:flex;align-items:center;justify-content:center;border:2px solid #0e534f;line-height:1}
  .tb-bell .nb-n[hidden]{display:none}
  .tb-bell.ring summary svg{animation:nbRing .9s ease 2}
  @keyframes nbRing{0%,100%{transform:rotate(0)}20%{transform:rotate(16deg)}40%{transform:rotate(-14deg)}60%{transform:rotate(9deg)}80%{transform:rotate(-5deg)}}
  .nb-panel{position:absolute;top:calc(100% + 6px);left:0;width:360px;max-width:calc(100vw - 16px);background:#fff;color:#1c2826;border:1px solid #dde3e1;border-radius:12px;
    box-shadow:0 12px 30px rgba(14,83,79,.18);z-index:30;overflow:hidden}
  .nb-head{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border-bottom:1px solid #eef0f0}
  .nb-head b{font-size:14.5px}
  .nb-head button{background:none;border:0;color:#0f5e5a !important;font:inherit;font-size:12.5px;cursor:pointer;padding:0}
  .nb-list{max-height:min(420px,60vh);overflow-y:auto}
  .nb-list a{display:flex;gap:10px;padding:10px 14px;border-bottom:1px solid #f1f3f3;color:#1c2826 !important;text-decoration:none}
  .nb-list a:hover{background:#f5f9f8}
  .nb-list a.u{background:#f0f7f6}
  .nb-list .d{width:8px;height:8px;border-radius:50%;margin-top:7px;flex:none}
  .nb-list a.u .d{background:#0f5e5a}
  .nb-list a.u.x .d{background:#d93025}
  .nb-list .t{flex:1;min-width:0}
  .nb-list .t b{display:block;font-size:13.5px;line-height:1.45}
  .nb-list .t p{margin:1px 0 0;font-size:12.5px;color:#5f6f6c;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;white-space:pre-line}
  .nb-list .t small{font-size:11px;color:#8a9895}
  .nb-empty{padding:26px 14px;text-align:center;color:#5f6f6c;font-size:13px}
  .nb-push{display:flex;align-items:center;gap:10px;padding:10px 14px;background:#fdf6e7;border-bottom:1px solid #f3e4c2;font-size:12.5px;color:#7a4f00}
  .nb-push[hidden]{display:none}
  .nb-push button{margin-inline-start:auto;background:#0f5e5a;color:#fff !important;border:0;border-radius:8px;padding:5px 10px;font:inherit;font-size:12.5px;font-weight:700;cursor:pointer;white-space:nowrap}
  .nb-foot{display:block;text-align:center;padding:9px;font-size:13px;font-weight:700;color:#0f5e5a !important;text-decoration:none;border-top:1px solid #eef0f0}
  .tb-user .menu .nbc{margin-inline-start:auto;background:#d93025;color:#fff;border-radius:99px;font-size:11px;font-weight:700;padding:0 7px}
  .tb-user .menu [hidden],[data-push][hidden],[data-install][hidden]{display:none !important}
  .nb-toast{position:fixed;bottom:18px;left:18px;z-index:60;background:#0e534f;color:#fff;border-radius:12px;padding:12px 16px;max-width:min(380px,calc(100vw - 36px));
    box-shadow:0 10px 30px rgba(0,0,0,.25);font-size:13.5px;line-height:1.6;cursor:pointer;animation:nbIn .25s ease}
  .nb-toast.x{background:#b3261e}
  .nb-toast b{display:block;font-size:14px}
  @keyframes nbIn{from{transform:translateY(20px);opacity:0}to{transform:none;opacity:1}}
  @media (max-width:980px){
    .tb-org b{max-width:150px} .tb-org span,.tb-user .nm,header.top .brand .bt{display:none} .tb-nav{margin-inline-start:6px} .tb-nav a span{display:none}
  }
  @media (max-width:560px){ header.top{gap:10px;padding:0 12px !important} .tb-div,header.top .brand{display:none !important} .tb-user .menu{position:fixed;left:8px;top:60px} .nb-panel{position:fixed;left:8px;right:8px;top:60px;width:auto} .tb-bell summary{width:38px;height:38px} .tb-org .lg{width:38px;height:38px} .tb-org b{font-size:14px;max-width:120px} }
  /* دليل الوثائق (زر المعلومات): الإجراءات · المؤشرات · الأوصاف الوظيفية */
  .lb-panel{position:absolute;top:calc(100% + 6px);left:0;width:400px;max-width:calc(100vw - 16px);background:#fff;color:#1c2826;border:1px solid #dde3e1;border-radius:12px;
    box-shadow:0 12px 32px rgba(0,0,0,.16);z-index:55;overflow:hidden}
  .lb-tabs{display:flex;border-bottom:1px solid #eef0f0}
  .lb-tabs button{flex:1;background:none;border:0;border-bottom:3px solid transparent;padding:10px 6px;font:inherit;font-size:13.5px;font-weight:700;color:#5f6f6c;cursor:pointer}
  .lb-tabs button.on{color:#0f5e5a;border-bottom-color:#0f5e5a}
  .lb-tabs small{display:block;font-size:11px;font-weight:600;color:#8a9895}
  .lb-q{padding:8px 10px;border-bottom:1px solid #eef0f0}
  .lb-q input{width:100%;padding:8px 10px;border:1px solid #dde3e1;border-radius:8px;font:inherit;font-size:13.5px}
  .lb-list{max-height:min(430px,58vh);overflow-y:auto}
  .lb-list a{display:flex;gap:10px;align-items:center;padding:9px 14px;border-bottom:1px solid #f1f3f3;color:#1c2826 !important;text-decoration:none;font-size:13.5px}
  .lb-list a:hover{background:#f5f9f8}
  .lb-list .c{direction:ltr;font-weight:700;color:#0f5e5a;min-width:86px;font-size:12.5px;white-space:nowrap}
  .lb-list .t{flex:1;min-width:0;line-height:1.5}
  .lb-list .s{font-size:11px;border-radius:99px;padding:2px 8px;white-space:nowrap;background:#f1f3f3;color:#5f6f6c}
  .lb-list .s.effective{background:#e5f4ea;color:#1e7a3c} .lb-list .s.pending{background:#fdf3dc;color:#8a5a00} .lb-list .s.obsolete{background:#fbe9e7;color:#b3261e}
  .lb-foot{display:block;text-align:center;padding:9px;font-size:13px;font-weight:700;color:#0f5e5a !important;text-decoration:none;border-top:1px solid #eef0f0}
  @media (max-width:560px){ .lb-panel{position:fixed;left:8px;right:8px;top:60px;width:auto} }
  @media print{ header.top{display:none !important} }
</style>
<script>
  /*
   * fitData(template, data): يعيد بيانات سجل محفوظ لنفس شكل النموذج الفارغ
   * الخادم كان يحول النص الفارغ إلى null والكائن الفارغ {} إلى []، فتتعطل الشاشة بعد فتح السجل.
   * النص null ← ''، والقائمة [] مكان كائن ← {}، وعناصر القوائم تأخذ شكل أول عنصر في النموذج.
   */
  window.fitData = function fit(tpl, data) {
    if (data === undefined) return tpl;
    if (tpl === null || tpl === undefined) return data;
    if (typeof tpl === 'string') return data === null ? '' : data;
    if (typeof tpl === 'number' || typeof tpl === 'boolean') return data === null ? tpl : data;
    if (Array.isArray(tpl)) {
      if (!Array.isArray(data)) return data && typeof data === 'object' ? Object.values(data) : tpl;
      const el = tpl[0];
      if (el && typeof el === 'object' && !Array.isArray(el)) return data.map(x => x && typeof x === 'object' && !Array.isArray(x) ? fit(Object.assign({}, el), x) : x);
      if (typeof el === 'string') return data.map(x => x === null ? '' : x);
      return data;
    }
    if (typeof tpl === 'object') {
      const obj = Array.isArray(data) ? Object.assign({}, data) : data;
      if (!obj || typeof obj !== 'object') return tpl;
      const out = Object.assign({}, tpl);
      const isMap = Object.keys(tpl).length === 0;           // مثل: نتائج الفحص {بند: نعم/لا}
      Object.keys(obj).forEach(k => { out[k] = k in tpl ? fit(tpl[k], obj[k]) : (isMap && obj[k] === null ? '' : obj[k]); });
      return out;
    }
    return data;
  };
</script>
@stack('head')
@if (auth()->check() && \App\Support\Audits::isAuditor(auth()->user()))
<style>
  /* المراجع الخارجي: علامة على كل المطبوعات + شريط «اطلاع فقط» */
  .aud-bar{background:#7a1f17;color:#fff;text-align:center;font-size:13px;font-weight:700;padding:6px 12px}
  @media print{
    .aud-bar{display:none !important}
    body::after{content:'نسخة للمراجع الخارجي · غير خاضعة للتحكم';position:fixed;top:45%;left:50%;transform:translate(-50%,-50%) rotate(-28deg);
      font-size:46px;font-weight:700;color:rgba(179,38,30,.14);white-space:nowrap;z-index:9999;pointer-events:none}
  }
</style>
@endif
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
    @if ($me && $me->tenant_id)
      <a href="{{ route('qms.map') }}" class="{{ request()->routeIs('qms.map') ? 'on' : '' }}">{!! \App\Support\Icon::svg('chart', 19) !!}<span>خريطة الجودة</span></a>
    @endif
    @if ($me && $me->isClientAdmin())
      <a href="{{ route('client.users.index') }}" class="{{ request()->routeIs('client.users.*') ? 'on' : '' }}">{!! \App\Support\Icon::svg('users', 19) !!}<span>المستخدمون</span></a>
    @endif
    @yield('nav')
  </nav>

  <span class="tb-sp"></span>

  @if ($me && $me->tenant_id)
    <details class="tb-bell" id="lbBox">
      <summary title="دليل الوثائق: الإجراءات والمؤشرات والأوصاف الوظيفية" aria-label="دليل الوثائق">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9.5"/><path d="M12 11v6"/><circle cx="12" cy="7.6" r=".6" fill="currentColor"/></svg>
      </summary>
      <div class="lb-panel">
        <div class="lb-tabs" id="lbTabs">
          <button type="button" class="on" data-t="sop">الإجراءات<small>SOP</small></button>
          <button type="button" data-t="kpi">مؤشرات الأداء<small>KPI</small></button>
          <button type="button" data-t="jd">الأوصاف الوظيفية<small>JD</small></button>
        </div>
        <div class="lb-q"><input type="search" id="lbQ" placeholder="ابحث بالكود أو الاسم… (مثال: SH-003، نظافة)" autocomplete="off"></div>
        <div class="lb-list" id="lbList"><div class="nb-empty">جارٍ التحميل…</div></div>
        <a class="lb-foot" href="{{ route('documents.index') }}">سجل الوثائق</a>
      </div>
    </details>

    <details class="tb-bell" id="nbBell">
      <summary title="الإشعارات" aria-label="الإشعارات">{!! \App\Support\Icon::svg('bell', 22) !!}<span class="nb-n" id="nbN" hidden>0</span></summary>
      <div class="nb-panel">
        <div class="nb-head"><b>الإشعارات</b><button type="button" id="nbAll">تعليم الكل كمقروء</button></div>
        <div class="nb-push" id="nbPush" hidden><span id="nbPushTx">فعّل الإشعارات على هذا الجهاز لتصلك التنبيهات بصوت.</span><button type="button" data-push="on">تفعيل</button></div>
        <div class="nb-list" id="nbList"><div class="nb-empty">جارٍ التحميل…</div></div>
        <a class="nb-foot" href="{{ route('notifications.index') }}">كل الإشعارات</a>
      </div>
    </details>
  @endif

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
        @if ($me->tenant_id)
          <a href="{{ route('notifications.index') }}">{!! \App\Support\Icon::svg('bell', 18) !!} الإشعارات <span class="nbc" id="nbMenuN" hidden></span></a>
          <a href="{{ route('daily.index') }}">{!! \App\Support\Icon::svg('tasks', 18) !!} مهام اليوم</a>
          @if (\App\Support\Audits::canManage($me) || \App\Support\Audits::isAuditor($me))
            <a href="{{ route('audits.index') }}">{!! \App\Support\Icon::svg('search', 18) !!} المراجعات الخارجية</a>
          @endif
          <a href="{{ route('workcards.mine') }}" target="_blank">{!! \App\Support\Icon::svg('clipcheck', 18) !!} بطاقة عملي</a>
          <a href="{{ route('tasks.index') }}">{!! \App\Support\Icon::svg('check', 18) !!} المهام المكلف بها</a>
          <button type="button" data-push="on" hidden>{!! \App\Support\Icon::svg('bell', 18) !!} تفعيل الإشعارات على هذا الجهاز</button>
          <button type="button" data-install hidden>{!! \App\Support\Icon::svg('home', 18) !!} تثبيت التطبيق على الجهاز</button>
          <a href="{{ route('jobs.mine') }}">{!! \App\Support\Icon::svg('file', 18) !!} وصفي الوظيفي</a>
          <a href="{{ route('training.employee', ['user' => $me->id]) }}">{!! \App\Support\Icon::svg('cap', 18) !!} سجل تدريبي</a>
        @endif
        <a href="{{ route('password.edit') }}">{!! \App\Support\Icon::svg('key', 18) !!} تغيير كلمة المرور</a>
        @if ($me->isClientAdmin())
          <a href="{{ route('client.notify.index') }}">{!! \App\Support\Icon::svg('bell', 18) !!} إعداد الإشعارات</a>
          <a href="{{ route('client.permissions.index') }}">{!! \App\Support\Icon::svg('shield', 18) !!} الصلاحيات</a>
          <a href="{{ route('client.settings.edit') }}">{!! \App\Support\Icon::svg('settings', 18) !!} إعدادات المخزن</a>
        @endif
        <hr>
        <form method="POST" action="{{ route('logout') }}" style="margin:0" id="logoutForm">
          @csrf
          <button type="submit" class="last">{!! \App\Support\Icon::svg('logout', 18) !!} تسجيل الخروج</button>
        </form>
      </div>
    </details>
  @endif
</header>

@if (auth()->check() && \App\Support\Audits::isAuditor(auth()->user()))
  <div class="aud-bar">حساب مراجع خارجي · اطلاع فقط على النظام · الكتابة في شاشة المراجعة · ينتهي <span class="ltr">{{ auth()->user()->access_until?->format('Y-m-d') }}</span> · <a href="{{ route('audits.index') }}" style="color:#ffd9d4">مراجعتي</a></div>
@endif
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
  // إغلاق قائمة المستخدم والجرس عند الضغط خارجهما
  document.addEventListener('click', e => { document.querySelectorAll('.tb-user, .tb-bell').forEach(d => { if (d.open && !d.contains(e.target)) d.open = false; }); });
</script>
@if ($me && $me->tenant_id)
<script>
  /* دليل الوثائق: يُحمّل مرة واحدة عند أول فتح */
  (function () {
    const box = document.getElementById('lbBox');
    if (!box) return;
    const URL = @json(route('library'));
    const list = document.getElementById('lbList'), q = document.getElementById('lbQ');
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const norm = s => String(s || '').toLowerCase().replace(/[\u064B-\u065F\u0640]/g, '').replace(/[أإآ]/g, 'ا').replace(/ة/g, 'ه').replace(/ى/g, 'ي');
    let data = null, tab = 'sop', loading = false;
    function draw() {
      if (!data) return;
      const w = norm(q.value.trim()).split(/\s+/).filter(Boolean);
      const rows = (data[tab] || []).filter(d => w.every(x => norm(d.code + ' ' + d.title).includes(x)));
      list.innerHTML = rows.length ? rows.map(d => `<a href="${esc(d.url)}"><span class="c">${esc(d.code)}</span><span class="t">${esc(d.title)}</span><span class="s ${esc(d.state)}">${esc(d.stateLabel)}</span></a>`).join('')
        : '<div class="nb-empty">لا توجد نتائج.</div>';
      document.querySelectorAll('#lbTabs button').forEach(b => {
        const n = (data[b.dataset.t] || []).length;
        b.querySelector('small').textContent = { sop: 'SOP', kpi: 'KPI', jd: 'JD' }[b.dataset.t] + ' · ' + n;
      });
    }
    box.addEventListener('toggle', () => {
      if (!box.open) return;
      setTimeout(() => q.focus({ preventScroll: true }), 30);
      if (data || loading) return;
      loading = true;
      fetch(URL, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(j => { data = j; draw(); })
        .catch(() => { list.innerHTML = '<div class="nb-empty">تعذر التحميل. أعد المحاولة.</div>'; })
        .finally(() => { loading = false; });
    });
    document.getElementById('lbTabs').addEventListener('click', e => {
      const b = e.target.closest('button'); if (!b) return;
      tab = b.dataset.t;
      document.querySelectorAll('#lbTabs button').forEach(x => x.classList.toggle('on', x === b));
      draw();
    });
    q.addEventListener('input', draw);
  })();
</script>
@endif
@if ($me && $me->tenant_id)
  @php
    $pushBoot = [
      'key'     => config('webpush.public_key'),
      'feed'    => route('notifications.feed'),
      'sub'     => route('notifications.subscribe'),
      'unsub'   => route('notifications.unsubscribe'),
      'test'    => route('notifications.test'),
      'readAll' => route('notifications.readAll'),
      'csrf'    => csrf_token(),
      'me'      => $me->id,
    ];
  @endphp
  <script>window.ETQAN_PUSH = @json($pushBoot);</script>
  <script>
  @verbatim
  (function () {
    const P = window.ETQAN_PUSH, $ = s => document.querySelector(s), $$ = s => document.querySelectorAll(s);
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const baseTitle = document.title;
    let last = null, items = [], audio = null;
    const store = { get: k => { try { return sessionStorage.getItem(k); } catch (e) { return null; } }, set: (k, v) => { try { sessionStorage.setItem(k, v); } catch (e) {} } };

    async function api(method, url, body) {
      const res = await fetch(url, { method, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': P.csrf, 'X-Requested-With': 'XMLHttpRequest' }, body: body ? JSON.stringify(body) : undefined, keepalive: method !== 'GET' });
      const json = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(json.message || 'تعذر الاتصال بالخادم.');
      return json;
    }

    /* ---------- الصوت داخل الصفحة (عند وصول إشعار والصفحة مفتوحة) ---------- */
    function unlockAudio() { try { audio = audio || new (window.AudioContext || window.webkitAudioContext)(); if (audio.state === 'suspended') audio.resume(); } catch (e) {} }
    document.addEventListener('pointerdown', unlockAudio, { once: true });
    function beep(urgent) {
      try {
        unlockAudio(); if (!audio) return;
        const tones = urgent ? [880, 660, 880, 660] : [784, 988];
        tones.forEach((f, i) => {
          const o = audio.createOscillator(), g = audio.createGain(), t = audio.currentTime + i * 0.18;
          o.type = 'sine'; o.frequency.value = f; o.connect(g); g.connect(audio.destination);
          g.gain.setValueAtTime(0.0001, t); g.gain.exponentialRampToValueAtTime(0.25, t + 0.02); g.gain.exponentialRampToValueAtTime(0.0001, t + 0.16);
          o.start(t); o.stop(t + 0.17);
        });
      } catch (e) {}
      if (navigator.vibrate) navigator.vibrate(urgent ? [200, 100, 200] : 120);
    }
    function toast(n) {
      const d = document.createElement('div');
      d.className = 'nb-toast' + (n.urgent ? ' x' : '');
      d.innerHTML = `<b>${esc(n.title)}</b>${esc(n.body || '').split('\n')[0]}`;
      d.onclick = () => { location.href = n.url; };
      document.body.appendChild(d);
      setTimeout(() => d.remove(), 7000);
    }

    /* ---------- الجرس ---------- */
    function paint(unread) {
      const n = $('#nbN'), m = $('#nbMenuN');
      n.hidden = !unread; n.textContent = unread > 99 ? '99+' : unread;
      if (m) { m.hidden = !unread; m.textContent = unread; }
      document.title = (unread ? `(${unread}) ` : '') + baseTitle;
    }
    function list() {
      $('#nbList').innerHTML = items.length ? items.map(n => `
        <a href="${esc(n.url)}" class="${n.read ? '' : 'u'} ${n.urgent ? 'x' : ''}"><span class="d"></span>
          <span class="t"><b>${esc(n.title)}</b>${n.body ? `<p>${esc(n.body)}</p>` : ''}<small>${esc(n.label)} · ${esc(n.ago)}</small></span></a>`).join('')
        : '<div class="nb-empty">لا توجد إشعارات بعد.</div>';
    }
    async function poll(fromPush) {
      try {
        const j = await api('GET', P.feed);
        const fresh = last !== null ? j.items.filter(n => n.id > last && !n.read) : [];
        items = j.items; paint(j.unread); list();
        if (fresh.length) {
          const bell = $('#nbBell'); bell.classList.remove('ring'); void bell.offsetWidth; bell.classList.add('ring');
          if (!fromPush) beep(fresh.some(n => n.urgent));
          if (!document.hidden && !fromPush) toast(fresh[0]);
        }
        last = j.last;
      } catch (e) {}
    }
    $('#nbBell').addEventListener('toggle', e => { if (e.target.open) poll(); });
    $('#nbAll').addEventListener('click', async () => { try { await api('POST', P.readAll); poll(); } catch (e) {} });
    poll();
    setInterval(() => { if (!document.hidden) poll(); }, 60000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });

    /* ---------- الإشعارات على الجهاز (Web Push) ---------- */
    const isIOS = /iPhone|iPad|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
    const supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
    let reg = null;

    function keyBytes(b64) {
      const s = (b64 + '='.repeat((4 - b64.length % 4) % 4)).replace(/-/g, '+').replace(/_/g, '/');
      return Uint8Array.from(atob(s), c => c.charCodeAt(0));
    }
    function sameKey(sub) {
      try { const k = sub.options && sub.options.applicationServerKey; if (!k) return true; const a = new Uint8Array(k), b = keyBytes(P.key); return a.length === b.length && a.every((v, i) => v === b[i]); } catch (e) { return true; }
    }
    async function state() {
      if (!window.isSecureContext) return 'insecure';
      if (isIOS && !standalone) return 'ios';
      if (!supported) return 'unsupported';
      if (!P.key) return 'server';
      if (Notification.permission === 'denied') return 'denied';
      const s = reg && await reg.pushManager.getSubscription();
      return s && Notification.permission === 'granted' ? 'on' : 'off';
    }
    const TEXT = {
      on: 'الإشعارات مفعّلة على هذا الجهاز ✓ ستصلك التنبيهات بصوت حتى والمتصفح مغلق.',
      off: 'الإشعارات غير مفعّلة على هذا الجهاز. اضغط «تفعيل» لتصلك التنبيهات بصوت.',
      denied: 'الإشعارات محظورة لهذا الموقع. افتح إعدادات المتصفح ← الموقع ← الإشعارات ← سماح، ثم أعد المحاولة.',
      ios: 'على الآيفون: اضغط زر المشاركة ⎋ ثم «إضافة إلى الشاشة الرئيسية»، وافتح إتقان من الأيقونة ثم فعّل الإشعارات (iOS 16.4 أو أحدث).',
      insecure: 'الإشعارات تحتاج اتصالاً آمناً (https). على الخادم المحلي شغّل: herd secure etqan',
      unsupported: 'هذا المتصفح لا يدعم الإشعارات. استخدم Chrome أو Edge أو Samsung Internet.',
      server: 'إشعارات الموبايل غير مفعلة على الخادم بعد (مفاتيح VAPID).',
    };
    async function refresh() {
      const st = await state();
      $$('[data-push="on"]').forEach(b => { b.hidden = st !== 'off'; });
      $$('[data-push="off"], [data-push="test"]').forEach(b => { b.hidden = st !== 'on'; });
      const box = $('#nbPush'); if (box) { box.hidden = st === 'on' || st === 'unsupported' || st === 'server'; $('#nbPushTx').textContent = TEXT[st]; box.querySelector('button').hidden = st !== 'off'; }
      const ps = $('#pushState'); if (ps) ps.textContent = TEXT[st];
      return st;
    }
    async function save(sub) { await api('POST', P.sub, sub.toJSON()); store.set('etqanPush', P.me + '|' + sub.endpoint); }
    async function enable() {
      try {
        unlockAudio();
        if (await state() !== 'off') return refresh();
        const perm = await Notification.requestPermission();
        if (perm !== 'granted') { await refresh(); return alert(TEXT.denied); }
        reg = reg || await navigator.serviceWorker.ready;
        let sub = await reg.pushManager.getSubscription();
        if (sub && !sameKey(sub)) { await sub.unsubscribe(); sub = null; }
        sub = sub || await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyBytes(P.key) });
        await save(sub);
        await refresh();
        await test();
      } catch (e) { alert('تعذر تفعيل الإشعارات: ' + e.message); refresh(); }
    }
    async function disable() {
      try {
        const sub = reg && await reg.pushManager.getSubscription();
        if (sub) { await api('POST', P.unsub, { endpoint: sub.endpoint }).catch(() => {}); await sub.unsubscribe(); }
      } catch (e) {}
      refresh();
    }
    async function test() {
      try { const j = await api('POST', P.test); toast({ title: 'إشعار تجريبي', body: j.message, url: location.href }); }
      catch (e) { toast({ title: 'إشعار تجريبي', body: e.message, url: location.href, urgent: true }); }
    }
    document.addEventListener('click', e => {
      const b = e.target.closest('[data-push]'); if (!b) return;
      ({ on: enable, off: disable, test: test })[b.dataset.push]?.();
    });

    if (supported && window.isSecureContext) {
      navigator.serviceWorker.register('/sw.js', { scope: '/' }).then(async r => {
        reg = r;
        await navigator.serviceWorker.ready;
        // الجهاز مفعّل: تأكيد ربطه بالمستخدم الحالي (جهاز مشترك · مسح بيانات الخادم)
        const sub = await reg.pushManager.getSubscription();
        if (sub && Notification.permission === 'granted' && P.key) {
          if (!sameKey(sub)) { await sub.unsubscribe(); }
          else if (store.get('etqanPush') !== P.me + '|' + sub.endpoint) { save(sub).catch(() => {}); }
        }
        refresh();
      }).catch(() => refresh());
      navigator.serviceWorker.addEventListener('message', e => { if (e.data && e.data.type === 'etqan-push') { beep(!!e.data.urgent); poll(true); } });
    } else {
      refresh();
    }

    /* ---------- تسجيل الخروج: إيقاف الإشعار عن هذا الجهاز ---------- */
    const lf = $('#logoutForm');
    if (lf) lf.addEventListener('submit', async e => {
      if (lf.dataset.done) return;
      e.preventDefault(); lf.dataset.done = '1';
      try {
        const sub = reg && await reg.pushManager.getSubscription();
        if (sub) await Promise.race([api('POST', P.unsub, { endpoint: sub.endpoint }), new Promise(r => setTimeout(r, 1500))]);
      } catch (err) {}
      store.set('etqanPush', '');
      lf.submit();
    });

    /* ---------- تثبيت التطبيق (أندرويد / كمبيوتر) ---------- */
    let installEvt = null;
    window.addEventListener('beforeinstallprompt', e => { e.preventDefault(); installEvt = e; $$('[data-install]').forEach(b => { b.hidden = false; }); });
    document.addEventListener('click', async e => {
      if (!e.target.closest('[data-install]') || !installEvt) return;
      installEvt.prompt(); await installEvt.userChoice.catch(() => {}); installEvt = null;
      $$('[data-install]').forEach(b => { b.hidden = true; });
    });
  })();
  @endverbatim
  </script>
@endif
@stack('scripts')
</body>
</html>