@extends('layouts.app')

@section('title', 'تقرير دقة التحضير')

@push('head')
<style>
  .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:16px}
  .stat{background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px 14px}
  .stat b{display:block;font-size:26px;line-height:1.2;direction:ltr;text-align:right}
  .stat span{font-size:13px;color:var(--muted)}
  .stat.bad b{color:var(--bad)} .stat.ok b{color:var(--ok)} .stat.warn b{color:var(--warn)}
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .code{font-weight:700;color:var(--brand);direction:ltr;display:inline-block;white-space:nowrap;font-size:13px}
  .by{font-size:11.5px;color:var(--muted)}
  table.t td{vertical-align:middle}
  tr.f-disc td{background:#fdecea} tr.f-train td{background:#fff7ec}
  .flag{display:inline-block;border-radius:99px;padding:2px 10px;font-size:12px;font-weight:700;white-space:nowrap}
  .flag.disc{background:var(--bad);color:#fff} .flag.train{background:#fdf1dc;color:#9a6300} .flag.ok{background:#e4f4ea;color:#1e7a46}
  .acc{font-weight:700;direction:ltr;display:inline-block}
  .acc.low{color:var(--bad)} .acc.mid{color:var(--warn)} .acc.good{color:var(--ok)}
  .bar{height:8px;background:#e9eeec;border-radius:99px;overflow:hidden;min-width:80px}
  .bar i{display:block;height:100%;background:var(--bad)}
  .two{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  @media (max-width:900px){.two{grid-template-columns:1fr}}
  .two .card{margin:0 0 16px}
  .trend{display:flex;gap:8px;align-items:flex-end;height:120px;padding-top:10px}
  .trend div{flex:1;text-align:center;font-size:11.5px;color:var(--muted)}
  .trend .col{background:var(--brand);border-radius:6px 6px 0 0;margin:0 auto 4px;width:70%}
  .trend .col.low{background:var(--bad)}
  .trend b{display:block;color:var(--ink);font-size:12px;direction:ltr}
  .legend{font-size:12.5px;color:var(--muted);margin:6px 0 10px}
  @media print{
    header.top,footer.foot,.no-print{display:none !important}
    main.wrap{padding:0;max-width:none}
    .card{border:0;padding:0}
    body{background:#fff;font-size:11px}
    *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  }
</style>
@endpush

@section('content')
  @php
    $cls = fn ($v) => $v === null ? '' : ($v >= $target ? 'good' : ($v >= $target - 3 ? 'mid' : 'low'));
    $flags = ['disc' => 'إجراء تأديبي', 'train' => 'تدريب', 'ok' => 'جيد'];
    $maxPts = max(1, (float) ($zones->max('pts') ?? 1));
  @endphp
  <div class="head-actions">
    <div>
      <h1 class="page-title">تقرير دقة التحضير · الزونات والمحضرون <span class="code" style="font-size:12px;color:var(--muted)">SH-004-F04</span></h1>
      <p class="page-sub" style="margin:0">{{ auth()->user()->tenant->name_ar }} · من الفحوصات العشوائية المعتمدة للمدير الصيدلي</p>
    </div>
    <div class="actions no-print">
      <form method="GET" action="{{ route('orderchecks.index') }}" style="margin:0;display:flex;gap:6px">
        <input type="month" name="month" value="{{ $month }}" onchange="this.form.submit()" style="width:auto;direction:ltr">
      </form>
      <a href="{{ $formUrl }}" class="btn">+ فحص جديد</a>
      <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
    </div>
  </div>

  <div class="stats">
    <div class="stat {{ $cls($all['wAcc']) === 'good' ? 'ok' : ($all['wAcc'] === null ? '' : 'bad') }}"><b>{{ $all['wAcc'] === null ? '—' : $all['wAcc'] . '%' }}</b><span>الدقة الموزونة (المستهدف {{ $target }}%)</span></div>
    <div class="stat"><b>{{ $all['lineAcc'] === null ? '—' : $all['lineAcc'] . '%' }}</b><span>دقة الأصناف</span></div>
    <div class="stat"><b>{{ $all['checks'] }}</b><span>فحوصات معتمدة</span></div>
    <div class="stat"><b>{{ $all['orders'] }}</b><span>طلبيات تم فحصها</span></div>
    <div class="stat"><b>{{ $all['lines'] }}</b><span>أصناف تم فحصها</span></div>
    <div class="stat {{ $all['bad'] ? 'bad' : 'ok' }}"><b>{{ $all['bad'] }}</b><span>أصناف بها خطأ · {{ $all['pts'] }} نقطة</span></div>
  </div>

  <div class="card">
    <h2 style="font-size:16px;margin:0 0 4px">الزونات · {{ $month }}</h2>
    <p class="legend">كل زون له محضر مسؤول. <span class="flag train">تدريب</span> عند {{ $train }} نقاط فأكثر في الشهر · <span class="flag disc">إجراء تأديبي</span> عند {{ $disc }} نقطة فأكثر. الأوزان والحدود من إعدادات المخزن.</p>
    <div class="tbl-wrap">
      <table class="t">
        <thead><tr><th>الزون</th><th>المحضر</th><th>أصناف</th><th>أخطاء</th><th style="min-width:120px">نقاط</th><th>الدقة</th><th>أنواع الأخطاء</th><th>التقييم</th></tr></thead>
        <tbody>
          @forelse ($zones as $z)
            <tr class="f-{{ $z['flag'] }}">
              <td><b>{{ $z['name'] }}</b></td>
              <td>{{ $z['pickers'] ?: '—' }}</td>
              <td>{{ $z['lines'] }}</td>
              <td>{{ $z['bad'] }}</td>
              <td><b>{{ $z['pts'] }}</b><div class="bar"><i style="width:{{ round($z['pts'] / $maxPts * 100) }}%"></i></div></td>
              <td><span class="acc {{ $cls($z['wAcc']) }}">{{ $z['wAcc'] }}%</span></td>
              <td class="by">{{ $z['types'] ?: '—' }}</td>
              <td><span class="flag {{ $z['flag'] }}">{{ $flags[$z['flag']] }}</span></td>
            </tr>
          @empty
            <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:20px">لا توجد فحوصات معتمدة في هذا الشهر.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="two">
    <div class="card">
      <h2 style="font-size:16px;margin:0 0 10px">المحضرون</h2>
      <div class="tbl-wrap">
        <table class="t">
          <thead><tr><th>المحضر</th><th>الزون</th><th>أصناف</th><th>أخطاء</th><th>نقاط</th><th>التقييم</th></tr></thead>
          <tbody>
            @forelse ($pickers as $p)
              <tr class="f-{{ $p['flag'] }}"><td><b>{{ $p['name'] }}</b></td><td class="by">{{ $p['zones'] }}</td><td>{{ $p['lines'] }}</td><td>{{ $p['bad'] }}</td><td><b>{{ $p['pts'] }}</b></td><td><span class="flag {{ $p['flag'] }}">{{ $flags[$p['flag']] }}</span></td></tr>
            @empty
              <tr><td colspan="6" class="by" style="text-align:center;padding:14px">—</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    <div class="card">
      <h2 style="font-size:16px;margin:0 0 4px">المراجعون (الكنترول)</h2>
      <p class="legend" style="margin-top:0">أخطاء مرت من المراجعة قبل الخروج.</p>
      <div class="tbl-wrap">
        <table class="t">
          <thead><tr><th>المراجع</th><th>أصناف</th><th>أخطاء مرت</th><th>نقاط</th><th>الدقة</th></tr></thead>
          <tbody>
            @forelse ($checkers as $c)
              <tr><td><b>{{ $c['name'] }}</b></td><td>{{ $c['lines'] }}</td><td>{{ $c['bad'] }}</td><td>{{ $c['pts'] }}</td><td><span class="acc {{ $cls($c['wAcc']) }}">{{ $c['wAcc'] }}%</span></td></tr>
            @empty
              <tr><td colspan="5" class="by" style="text-align:center;padding:14px">—</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="two">
    <div class="card">
      <h2 style="font-size:16px;margin:0 0 10px">أنواع الأخطاء</h2>
      @forelse ($types as $t)
        <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed var(--line)"><span>{{ $t['label'] }}</span><b>{{ $t['count'] }}</b></div>
      @empty
        <p class="by">لا توجد أخطاء.</p>
      @endforelse
    </div>
    <div class="card">
      <h2 style="font-size:16px;margin:0">الدقة الموزونة · آخر ٦ أشهر</h2>
      <div class="trend">
        @foreach ($trend as $m)
          <div>
            @if ($m['wAcc'] !== null)
              <b>{{ $m['wAcc'] }}%</b>
              <div class="col {{ $m['wAcc'] < $target ? 'low' : '' }}" style="height:{{ max(4, round(($m['wAcc'] - 50) * 1.6)) }}px"></div>
            @else
              <b>—</b><div class="col" style="height:2px;background:#dde3e1"></div>
            @endif
            {{ $m['month'] }}
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <div class="card">
    <h2 style="font-size:16px;margin:0 0 10px">الفحوصات في {{ $month }}</h2>
    <div class="tbl-wrap">
      <table class="t">
        <thead><tr><th>الرقم</th><th>التاريخ</th><th>الرند</th><th>القائم بالفحص</th><th>طلبيات</th><th>أصناف</th><th>أخطاء</th><th>الدقة</th></tr></thead>
        <tbody>
          @forelse ($checks as $r)
            @php($t = $r->data['totals'] ?? [])
            <tr>
              <td><span class="code">{{ $r->number }}</span></td>
              <td><span class="ltr">{{ $r->record_date?->toDateString() }}</span></td>
              <td>{{ $r->data['round'] ?? '' }}</td>
              <td>{{ $r->data['inspector'] ?? '' }}</td>
              <td>{{ $t['orders'] ?? '' }}</td>
              <td>{{ $t['lines'] ?? '' }}</td>
              <td>{{ $t['bad'] ?? '' }}</td>
              <td><span class="acc {{ $cls($t['wAcc'] ?? null) }}">{{ isset($t['wAcc']) ? $t['wAcc'] . '%' : '—' }}</span></td>
            </tr>
          @empty
            <tr><td colspan="8" class="by" style="text-align:center;padding:14px">لا توجد فحوصات.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection