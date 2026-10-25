@extends('layouts.app')

@section('title', 'الرئيسية')

@push('head')
<style>
  main.wrap{max-width:1560px !important}
  .hd{--t:#0e534f;--t2:#1b6e68;--ink:#1c2826;--mut:#6b7a77;--ln:#e3e8e6;--soft:#f6f8f7}
  .hd{display:grid;grid-template-columns:290px minmax(0,1fr);gap:22px;align-items:start}
  @media (max-width:1150px){.hd{grid-template-columns:1fr}.hd-side{order:-1}}
  .hd a{text-decoration:none;color:inherit}
  .hd .ic{flex:none}

  /* الترحيب والمؤشرات */
  .hd-top{display:grid;grid-template-columns:minmax(330px,430px) minmax(0,1fr);gap:18px;align-items:stretch;margin-bottom:18px}
  @media (max-width:1300px){.hd-top{grid-template-columns:1fr}}
  .kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
  @media (max-width:900px){.kpis{grid-template-columns:repeat(2,1fr)}}
  .kpi{background:#fff;border:1px solid var(--ln);border-radius:14px;padding:16px;display:flex;justify-content:space-between;align-items:center;gap:10px;min-height:96px;transition:box-shadow .15s}
  .kpi:hover{box-shadow:0 6px 18px rgba(14,83,79,.08)}
  .kpi .l{font-size:13.5px;color:var(--mut);font-weight:600}
  .kpi .v{font-size:28px;font-weight:700;color:var(--ink);line-height:1.3;direction:ltr;text-align:right}
  .kpi .h{font-size:11.5px;color:var(--mut)}
  .chip{width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex:none}
  .t-teal{background:#e1f1ee;color:#0f6e64} .t-blue{background:#e2eef8;color:#2a6aa3} .t-green{background:#e4f4ea;color:#1e7a46}
  .t-orange{background:#fdeedc;color:#c46b12} .t-red{background:#fbe4e2;color:#c0362c} .t-purple{background:#ece8f8;color:#6a4fb3}
  .ring{position:relative;width:62px;height:62px;flex:none}
  .ring svg{transform:rotate(-90deg)}
  .ring b{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#1e7a46}
  .welcome h1{margin:0;font-size:26px;color:var(--ink)}
  .welcome .s{color:#0f6e64;font-size:16px;font-weight:600;margin-top:2px}
  .banner{margin-top:14px;background:linear-gradient(135deg,#e6f3f0,#f3f9f8);border:1px solid #d3e8e3;border-radius:14px;padding:14px 18px}
  .banner b{display:flex;align-items:center;gap:8px;font-size:16px;color:var(--t)}
  .banner p{margin:4px 0 0;font-size:13px;color:#3f5653}

  /* البحث */
  .bar{display:flex;gap:10px;margin:4px 0 22px;flex-wrap:wrap}
  .bar .q{flex:1;min-width:220px;position:relative}
  .bar .q input{width:100%;height:46px;border:1px solid var(--ln);border-radius:12px;padding:0 44px 0 14px;font:inherit;background:#fff}
  .bar .q .ic{position:absolute;inset-inline-start:14px;top:13px;color:var(--mut)}
  .bar select{height:46px;border:1px solid var(--ln);border-radius:12px;padding:0 14px;font:inherit;background:#fff;min-width:200px;width:auto}
  .bar .sq{width:46px;height:46px;border:1px solid var(--ln);border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;color:var(--t)}

  /* الأقسام والبطاقات */
  .sec{margin-bottom:26px}
  .sec-h{display:flex;align-items:center;gap:10px;margin-bottom:12px}
  .sec-h h2{margin:0;font-size:19px;color:var(--ink);white-space:nowrap}
  .sec-h .ic{color:var(--t)}
  .sec-h i{flex:1;height:1px;background:var(--ln)}
  .cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
  .cards.c3{grid-template-columns:repeat(3,minmax(0,1fr))}
  @media (max-width:1300px){.cards,.cards.c3{grid-template-columns:repeat(2,minmax(0,1fr))}}
  @media (max-width:640px){.cards,.cards.c3{grid-template-columns:1fr}}
  .cd{background:#fff;border:1px solid var(--ln);border-radius:14px;padding:16px 16px 12px;display:flex;flex-direction:column;gap:6px;position:relative;transition:box-shadow .15s,border-color .15s;min-height:132px}
  .cd:hover{box-shadow:0 8px 22px rgba(14,83,79,.09);border-color:#cfe1dd}
  .cd .tp{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
  .cd .tp b{font-size:15.5px;color:var(--ink);line-height:1.4}
  .cd .tp .chip{width:46px;height:46px}
  .cd .ds{font-size:12.5px;color:var(--mut);line-height:1.7;flex:1}
  .cd .ft{display:flex;align-items:center;gap:8px;margin-top:4px;font-size:13px;font-weight:700;color:var(--t);flex-wrap:wrap}
  .cd .ft .code{margin-inline-start:auto;font-size:10.5px;font-weight:600;color:#a3b0ad;direction:ltr}
  .cd .ft .go{display:flex;align-items:center;gap:4px}
  .cd .ft .w{background:#fbe4e2;color:#c0362c;border-radius:99px;padding:1px 9px;font-size:11.5px}
  .cd .lk{display:flex;gap:6px;flex-wrap:wrap;margin-top:2px}
  .cd .lk a{font-size:12px;font-weight:600;color:#2a6aa3;background:#eef4fa;border-radius:99px;padding:2px 10px;position:relative;z-index:2}
  .cd .lk a:hover{background:#dfeaf6}
  .cd .cover{position:absolute;inset:0;border-radius:14px;z-index:1}
  .cd.soon{background:var(--soft);cursor:default}
  .cd.soon .chip{filter:grayscale(1);opacity:.6}
  .cd.soon .tp b,.cd.soon .ds{color:#98a5a2}
  .cd .badge-soon{font-size:11.5px;font-weight:700;color:#8a6d1e;background:#f8efdc;border-radius:99px;padding:2px 10px}
  .empty-filter{display:none;text-align:center;color:var(--mut);padding:30px;background:#fff;border:1px dashed var(--ln);border-radius:14px}

  /* الجانب */
  .hd-side{display:flex;flex-direction:column;gap:16px;position:sticky;top:16px}
  @media (max-width:1150px){.hd-side{position:static}}
  .pn{background:#fff;border:1px solid var(--ln);border-radius:14px;padding:16px}
  .pn h3{margin:0 0 12px;font-size:16px;display:flex;align-items:center;gap:8px;color:var(--ink)}
  .pn h3 .ic{color:var(--t)}
  .at{display:flex;gap:10px;align-items:flex-start;border-radius:12px;padding:11px 12px;margin-bottom:8px;border:1px solid transparent}
  .at.warn{background:#fff6e8;border-color:#f6e2bf} .at.bad{background:#fdf0ee;border-color:#f3cfca} .at.info{background:#eef5f9;border-color:#d6e6f1}
  .at .ai{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex:none}
  .at.warn .ai{background:#f6a623;color:#fff} .at.bad .ai{background:#e15a4f;color:#fff} .at.info .ai{background:#3c86c4;color:#fff}
  .at b{display:block;font-size:13.5px;color:var(--ink)}
  .at span{display:block;font-size:12px;color:var(--mut);margin-top:2px}
  .at .ch{margin-inline-start:auto;color:var(--mut);align-self:center}
  .ok-box{display:flex;align-items:center;gap:8px;background:#eef8f2;color:#1e7a46;border-radius:12px;padding:12px;font-size:13.5px;font-weight:600}
  .act{display:flex;gap:10px;padding:10px 0;border-bottom:1px solid #eef1f0}
  .act:last-child{border-bottom:0}
  .act .ai{width:32px;height:32px;border-radius:50%;background:#e1f1ee;color:#0f6e64;display:flex;align-items:center;justify-content:center;flex:none}
  .act b{display:block;font-size:13px;color:var(--ink);line-height:1.5}
  .act span{display:block;font-size:11.5px;color:var(--mut)}
  .act em{font-style:normal;font-size:11px;color:#98a5a2;white-space:nowrap;margin-inline-start:auto}
  .tiles-admin{margin-top:6px}
</style>
@endpush

@section('content')
@if ($user->isSuperAdmin())
  <h1 class="page-title">مرحباً، {{ $user->displayName() }}</h1>
  <p class="page-sub">مدير النظام</p>
  <div class="tiles">
    <a href="{{ route('admin.tenants.index') }}" class="tile"><b>العملاء</b><span>الشركات والمخازن · الشعار · الحالة · مدير كل عميل</span></a>
    <a href="{{ route('admin.tenants.create') }}" class="tile"><b>+ عميل جديد</b><span>إنشاء عميل بالاسم العربي والإنجليزي والشعار</span></a>
    <div class="tile soon"><b>الأدوار الوظيفية</b><span>عرض الأدوار المتاحة في النظام</span></div>
  </div>
@else
  @php
    $D = \App\Support\HomeDashboard::for($user);
    $I = fn ($n, $s = 20) => \App\Support\Icon::svg($n, $s);
    $cv = $D['compliance']['value'];
    $circ = 2 * M_PI * 26;
  @endphp

  <div class="hd">
    {{-- ===================== الجانب ===================== --}}
    <aside class="hd-side">
      <div class="pn" id="attention">
        <h3>{!! $I('bell', 20) !!} يتطلب انتباهك @if (count($D['alerts']))<span style="margin-inline-start:auto;background:#fbe4e2;color:#c0362c;border-radius:99px;padding:0 9px;font-size:12px">{{ collect($D['alerts'])->sum('n') }}</span>@endif</h3>
        @forelse ($D['alerts'] as $a)
          <a href="{{ $a['url'] }}" class="at {{ $a['tone'] }}">
            <span class="ai">{!! $I($a['icon'], 16) !!}</span>
            <div><b>{{ $a['title'] }}</b><span>{{ $a['sub'] }}</span></div>
            <span class="ch">{!! $I('chevron', 16) !!}</span>
          </a>
        @empty
          <div class="ok-box">{!! $I('check', 18) !!} لا يوجد ما يحتاج انتباهك الآن</div>
        @endforelse
      </div>

      <div class="pn">
        <h3>{!! $I('chart', 20) !!} آخر الأنشطة</h3>
        @forelse ($D['activity'] as $a)
          <a href="{{ $a['url'] }}" class="act">
            <span class="ai">{!! $I($a['icon'], 16) !!}</span>
            <div style="min-width:0"><b>{{ $a['title'] }}</b><span>{{ $a['sub'] }}</span></div>
            <em>{{ $a['ago'] }}</em>
          </a>
        @empty
          <div style="font-size:13px;color:var(--mut)">لا توجد أنشطة بعد.</div>
        @endforelse
      </div>
    </aside>
    {{-- ===================== المحتوى ===================== --}}
    <div class="hd-main">
      <div class="hd-top">
        <div class="welcome">
          <h1>مرحباً، {{ $user->displayName() }}</h1>
          <div class="s">بوابة الجودة والامتثال التشغيلي</div>
          <div class="banner">
            <b>{!! $I('shield', 22) !!} منظومة الجودة</b>
            <p>إدارة موحدة لإجراءات الجودة، ومتابعة الالتزام، وتحسين الأداء في {{ $user->tenant?->name_ar }}.</p>
          </div>
        </div>
        <div class="kpis">
          @foreach ($D['kpis'] as $k)
            <a href="{{ $k['url'] }}" class="kpi">
              <div><div class="l">{{ $k['label'] }}</div><div class="v">{{ $k['value'] }}</div><div class="h">{{ $k['hint'] }}</div></div>
              <span class="chip t-{{ $k['tone'] }}">{!! $I($k['icon'], 26) !!}</span>
            </a>
          @endforeach
          <div class="kpi" title="{{ collect($D['compliance']['parts'])->map(fn ($v, $k) => $k . ': ' . $v . '%')->implode(' · ') ?: 'لا توجد بيانات كافية بعد' }}">
            <div><div class="l">نسبة الالتزام</div><div class="v">{{ $cv === null ? '—' : $cv . '%' }}</div><div class="h">هذا الشهر</div></div>
            <span class="ring">
              <svg width="62" height="62" viewBox="0 0 62 62"><circle cx="31" cy="31" r="26" fill="none" stroke="#e4f4ea" stroke-width="6"/>
                <circle cx="31" cy="31" r="26" fill="none" stroke="{{ $cv === null ? '#c9d3d1' : ($cv >= 90 ? '#1e7a46' : ($cv >= 75 ? '#d99a1e' : '#c0362c')) }}" stroke-width="6" stroke-linecap="round"
                  stroke-dasharray="{{ round($circ, 1) }}" stroke-dashoffset="{{ round($circ * (1 - ($cv ?? 0) / 100), 1) }}"/></svg>
              <b>{!! $I('check', 20) !!}</b>
            </span>
          </div>
        </div>
      </div>

      <div class="bar">
        @if ($user->isClientAdmin())
          <a href="{{ route('client.settings.edit') }}" class="sq" title="إعدادات المخزن">{!! $I('settings', 20) !!}</a>
        @endif
        <select id="secFilter" aria-label="القسم">
          <option value="">جميع الأقسام</option>
          @foreach ($D['sections'] as $s)<option value="{{ $s['key'] }}">{{ $s['title'] }}</option>@endforeach
        </select>
        <label class="q">
          {!! $I('search', 20) !!}
          <input type="search" id="q" placeholder="ابحث عن إجراء أو نموذج أو تقرير… (مثال: حرارة، SH-008)" autocomplete="off">
        </label>
      </div>

      @foreach ($D['sections'] as $s)
        <section class="sec" data-sec="{{ $s['key'] }}">
          <div class="sec-h">{!! $I($s['icon'], 22) !!}<h2>{{ $s['title'] }}</h2><i></i></div>
          <div class="cards {{ $s['cols'] === 3 ? 'c3' : '' }}">
            @foreach ($s['cards'] as $c)
              @if (! empty($c['soon']))
                <div class="cd soon" data-txt="{{ $c['title'] }} {{ $c['desc'] }} {{ $c['code'] ?? '' }}">
                  <div class="tp"><b>{{ $c['title'] }}</b><span class="chip t-{{ $c['tone'] }}">{!! $I($c['icon'], 22) !!}</span></div>
                  <div class="ds">{{ $c['desc'] }}</div>
                  <div class="ft"><span class="badge-soon">قريباً</span></div>
                </div>
              @else
                <div class="cd" data-txt="{{ $c['title'] }} {{ $c['desc'] }} {{ $c['code'] ?? '' }} {{ collect($c['links'] ?? [])->pluck(0)->implode(' ') }}">
                  <a href="{{ $c['url'] }}" class="cover" aria-label="{{ $c['title'] }}"></a>
                  <div class="tp"><b>{{ $c['title'] }}</b><span class="chip t-{{ $c['tone'] }}">{!! $I($c['icon'], 22) !!}</span></div>
                  <div class="ds">{{ $c['desc'] }}</div>
                  @if (! empty($c['links']))
                    <div class="lk">@foreach ($c['links'] as $l)<a href="{{ $l[1] }}">{{ $l[0] }}</a>@endforeach</div>
                  @endif
                  <div class="ft"><span class="go">{{ $c['count'] }} {!! $I('chevron', 16) !!}</span>@if (! empty($c['warn']))<span class="w">{{ $c['warn'] }}</span>@endif
                    @if (! empty($c['code']))<span class="code">{{ $c['code'] }}</span>@endif</div>
                </div>
              @endif
            @endforeach
          </div>
        </section>
      @endforeach
      <div class="empty-filter" id="noRes">لا توجد نتائج. جرّب كلمة أخرى.</div>
    </div>

  </div>
@endif
@endsection

@push('scripts')
<script>
(function () {
  const q = document.getElementById('q'), sf = document.getElementById('secFilter');
  if (!q) return;
  const norm = s => (s || '').toLowerCase().replace(/[أإآ]/g, 'ا').replace(/ة/g, 'ه').replace(/ى/g, 'ي');
  function apply() {
    const t = norm(q.value.trim()), sec = sf.value;
    let any = false;
    document.querySelectorAll('.sec').forEach(s => {
      const okSec = !sec || s.dataset.sec === sec;
      let n = 0;
      s.querySelectorAll('.cd').forEach(c => {
        const show = okSec && (!t || norm(c.dataset.txt).includes(t));
        c.style.display = show ? '' : 'none'; if (show) n++;
      });
      s.style.display = n ? '' : 'none'; if (n) any = true;
    });
    document.getElementById('noRes').style.display = any ? 'none' : 'block';
  }
  q.addEventListener('input', apply); sf.addEventListener('change', apply);
})();
</script>
@endpush