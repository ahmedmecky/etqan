@extends('layouts.app')

@section('title', 'الهيكل الوظيفي')

@push('head')
<style>
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:16px}
  .stat{background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px 14px}
  .stat b{display:block;font-size:26px;line-height:1.2;direction:ltr;text-align:right}
  .stat span{font-size:13px;color:var(--muted)}
  .stat.bad b{color:var(--bad)} .stat.ok b{color:var(--ok)} .stat.warn b{color:var(--warn)}
  .v{direction:ltr;font-variant-numeric:tabular-nums;white-space:nowrap}
  .code{font-weight:700;color:var(--brand);direction:ltr;display:inline-block;font-size:12.5px}
  .by{font-size:11.5px;color:var(--muted)}
  .pill{display:inline-block;border-radius:99px;padding:2px 10px;font-size:12px;font-weight:700;white-space:nowrap}
  .p-ok{background:#e3f4ea;color:var(--ok)} .p-vac{background:#fde8e6;color:var(--bad)} .p-cov{background:#fdf1dc;color:#9a6300} .p-na{background:#eef0f0;color:#8a9895}
  tr.na td{opacity:.5}
  tr.dept td{background:#f3f7f6;font-weight:700;color:var(--brand)}
  .info{background:#eef6f5;border:1px solid #d3e8e3;border-radius:10px;padding:10px 14px;font-size:13.5px;line-height:1.9;margin-bottom:14px}
</style>
@endpush

@section('content')
  @php
    $active = $rows->where('mode', '!=', 'na');
    $vacant = $rows->where('needs', true);
    $open = $vacant->filter(fn ($r) => $r['assigned'] < $r['groups']);
    $lastDept = null;
  @endphp
  <div class="head-actions">
    <div>
      <h1 class="page-title">الهيكل الوظيفي</h1>
      <p class="page-sub" style="margin:0">{{ auth()->user()->tenant->name_ar }} · الوظائف الموجودة في المخزن وعدد شاغليها وتوزيع مهام الوظائف الشاغرة · <span class="ltr">SH-021</span></p>
    </div>
    <div class="actions"><a href="{{ route('client.users.index') }}" class="btn ghost">المستخدمون</a></div>
  </div>

  <div class="info">
    لكل وظيفة من الـ {{ \App\Support\JobLibrary::all()->count() }} وظيفة في مكتبة الأوصاف الوظيفية اختر حالتها في مخزنك: <b>لها موظف مخصص</b>، أو <b>بدون موظف</b> وتُوزع مهامها على زملاء، أو <b>النشاط غير موجود</b> (مثل: لا يوجد بيع قطاعي).
    الوظيفة التي لها موظف مخصص ولا يشغلها أحد حالياً تظهر <b style="color:var(--bad)">شاغرة</b>: وزّع مجموعات مهامها (أ، ب، ج…) على الوظائف الأخرى حتى تُطبع في الوصف الوظيفي لمن استلمها.
    في المخازن الصغيرة يمكن للموظف الواحد أن يشغل أكثر من وظيفة من شاشة المستخدم.
  </div>

  <div class="stats">
    <div class="stat"><b>{{ $active->count() }}</b><span>وظيفة في المخزن</span></div>
    <div class="stat ok"><b>{{ $active->sum('count') }}</b><span>شغل وظيفي (أساسي + إضافي)</span></div>
    <div class="stat {{ $vacant->count() ? 'warn' : 'ok' }}"><b>{{ $vacant->count() }}</b><span>وظيفة بدون شاغل</span></div>
    <div class="stat {{ $open->count() ? 'bad' : 'ok' }}"><b>{{ $open->count() }}</b><span>مهامها لم تُوزع بالكامل</span></div>
    <div class="stat {{ $noJob->count() ? 'warn' : '' }}"><b>{{ $noJob->count() }}</b><span>مستخدم بدون وصف وظيفي</span></div>
  </div>

  @if ($noJob->count())
    <div class="card" style="padding:12px 16px">
      <b style="font-size:14px">مستخدمون بدون وصف وظيفي:</b>
      @foreach ($noJob as $u)<a href="{{ route('client.users.edit', $u) }}" class="pill p-cov" style="margin:2px">{{ $u->displayName() }}</a>@endforeach
    </div>
  @endif

  <div class="card">
    <div class="tbl-wrap">
      <table class="t">
        <thead><tr><th>الكود</th><th>الوظيفة</th><th>الحالة في المخزن</th><th>العدد المخطط</th><th>الشاغلون</th><th>المهام عند الشغور</th><th></th></tr></thead>
        <tbody>
          @foreach ($rows as $r)
            @if ($r['dept'] !== $lastDept)
              <tr class="dept"><td colspan="7">{{ $r['dept'] }}</td></tr>
              @php
                $lastDept = $r['dept'];
              @endphp
            @endif
            <tr class="{{ $r['mode'] === 'na' ? 'na' : '' }}">
              <td><span class="code">{{ $r['code'] }}</span></td>
              <td><b>{{ $r['title'] }}</b></td>
              <td>
                @if ($r['mode'] === 'na')<span class="pill p-na">غير موجود</span>
                @elseif ($r['mode'] === 'covered')<span class="pill p-cov">بدون موظف · تُوزع</span>
                @elseif ($r['count'] === 0)<span class="pill p-vac">شاغرة</span>
                @else<span class="pill p-ok">لها موظف</span>@endif
              </td>
              <td class="v">{{ $r['mode'] === 'staffed' ? ($r['planned'] ?: '—') : '' }}
                @if ($r['mode'] === 'staffed' && $r['planned'] && $r['count'] < $r['planned'])<span class="by" style="color:var(--bad)">(ينقص {{ $r['planned'] - $r['count'] }})</span>@endif</td>
              <td><b class="v">{{ $r['count'] }}</b> @if ($r['people']->count())<div class="by">{{ $r['people']->take(4)->implode('، ') }}{{ $r['people']->count() > 4 ? '…' : '' }}</div>@endif</td>
              <td>
                @if ($r['needs'])
                  <span class="{{ $r['assigned'] < $r['groups'] ? 'pill p-vac' : 'pill p-ok' }}">وُزع {{ $r['assigned'] }} من {{ $r['groups'] }}</span>
                @else <span class="by">—</span> @endif
              </td>
              <td class="actions" style="gap:4px;flex-wrap:nowrap">
                <a href="{{ route('jobs.edit', $r['code']) }}" class="btn ghost small">إعداد</a>
                <a href="{{ route('jobs.print', $r['code']) }}" class="btn ghost small" target="_blank">طباعة</a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endsection