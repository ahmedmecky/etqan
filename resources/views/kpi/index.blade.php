@extends('layouts.app')

@section('title', 'سجل تقييمات الأداء')

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
  .sc{font-weight:800;direction:ltr;display:inline-block;font-size:15px}
  .rt{display:inline-block;border-radius:99px;padding:2px 10px;font-size:12px;font-weight:700;color:#fff}
  .st{font-size:12.5px;font-weight:600}
  .st.final{color:var(--ok)} .st.mgr,.st.emp{color:var(--warn)}
  tr.weak td{background:#fff7f6}
  .two{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  @media (max-width:900px){.two{grid-template-columns:1fr}}
  .two .card{margin:0 0 16px}
  .bandrow{display:flex;align-items:center;gap:8px;padding:5px 0}
  .bandrow .lbl{width:150px;font-size:13.5px}
  .bandrow .bar{flex:1;height:14px;background:#eef0f0;border-radius:99px;overflow:hidden}
  .bandrow .bar i{display:block;height:100%}
  .bandrow b{width:30px;text-align:center}
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
    $stLabel = ['draft' => 'مسودة', 'mgr' => 'بانتظار الموظف', 'emp' => 'بانتظار الموارد البشرية', 'final' => 'معتمد'];
    $maxBand = max(1, $bands->max('count'));
  @endphp
  <div class="head-actions">
    <div>
      <h1 class="page-title">سجل تقييمات الأداء الشهرية</h1>
      <p class="page-sub" style="margin:0">{{ auth()->user()->tenant->name_ar }} · {{ $all ? 'كل التقييمات' : 'التقييمات التي كتبتها أو التي تخصك' }}</p>
    </div>
    <div class="actions no-print">
      <form method="GET" action="{{ route('kpi.index') }}" style="margin:0;display:flex;gap:6px">
        <input type="month" name="month" value="{{ $month }}" onchange="this.form.submit()" style="width:auto;direction:ltr">
        <select name="job" onchange="this.form.submit()" style="width:auto">
          <option value="">كل الوظائف</option>
          @foreach ($jobs as $k => $j)<option value="{{ $k }}" {{ $job === $k ? 'selected' : '' }}>{{ $j['title'] }}</option>@endforeach
        </select>
      </form>
      <a href="{{ $formUrl }}" class="btn">+ تقييم جديد</a>
      <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
    </div>
  </div>

  <div class="stats">
    <div class="stat"><b>{{ $stats['count'] }}</b><span>تقييم في {{ $month }}</span></div>
    <div class="stat ok"><b>{{ $stats['final'] }}</b><span>معتمد ومغلق</span></div>
    <div class="stat {{ $stats['pending'] ? 'warn' : '' }}"><b>{{ $stats['pending'] }}</b><span>لم يكتمل اعتماده</span></div>
    <div class="stat"><b>{{ $stats['avg'] === null ? '—' : $stats['avg'] . '%' }}</b><span>متوسط النتيجة</span></div>
    <div class="stat {{ $stats['weak'] ? 'bad' : '' }}"><b>{{ $stats['weak'] }}</b><span>أقل من ٦٥٪ أو بمخالفة جوهرية</span></div>
  </div>

  <div class="two">
    <div class="card">
      <h2 style="font-size:16px;margin:0 0 8px">توزيع التقديرات</h2>
      @foreach ($bands as $b)
        <div class="bandrow"><span class="lbl">{{ $b['name'] }}</span><div class="bar"><i style="width:{{ round($b['count'] / $maxBand * 100) }}%;background:{{ $b['color'] }}"></i></div><b>{{ $b['count'] }}</b></div>
      @endforeach
    </div>
    <div class="card">
      <h2 style="font-size:16px;margin:0 0 8px">حسب الوظيفة</h2>
      <table class="t">
        <thead><tr><th>الوظيفة</th><th>عدد</th><th>المتوسط</th><th>الأقل</th></tr></thead>
        <tbody>
          @forelse ($byJob as $j)
            <tr><td>{{ $j['title'] }}</td><td>{{ $j['count'] }}</td><td><b class="ltr">{{ $j['avg'] }}%</b></td><td class="ltr">{{ $j['min'] }}%</td></tr>
          @empty
            <tr><td colspan="4" class="by" style="text-align:center;padding:12px">—</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="tbl-wrap">
      <table class="t">
        <thead><tr><th>الرقم</th><th>الموظف</th><th>الوظيفة</th><th>المقيِّم</th><th>النتيجة</th><th>التقدير</th><th>تحتاج تحسين</th><th>الحالة</th></tr></thead>
        <tbody>
          @forelse ($rows as $r)
            @php($b = \App\Http\Controllers\KpiController::band($r['score']))
            <tr class="{{ ($r['score'] !== null && $r['score'] < 0.65) || $r['gates'] ? 'weak' : '' }}">
              <td><a class="code" href="{{ $formUrl }}?open={{ urlencode($r['number']) }}">{{ $r['number'] }}</a></td>
              <td><b>{{ $r['emp'] }}</b>@if ($r['code'])<div class="by ltr">{{ $r['code'] }}</div>@endif</td>
              <td>{{ $r['jobTitle'] }}@if ($r['variant'] && $r['variant'] !== $r['jobTitle'])<div class="by">{{ $r['variant'] }}</div>@endif</td>
              <td>{{ $r['evaluator'] }}</td>
              <td><span class="sc" style="color:{{ $b['color'] }}">{{ $r['score'] === null ? '—' : number_format($r['score'] * 100, 1) . '%' }}</span>
                @if ($r['gates'])<div class="by" style="color:var(--bad)">مخالفة جوهرية (سقف ٧٠٪)</div>@endif</td>
              <td>@if ($r['score'] !== null)<span class="rt" style="background:{{ $b['color'] }}">{{ $b['name'] }}</span>@endif</td>
              <td class="by ltr">{{ implode(' · ', $r['low']) ?: '—' }}</td>
              <td><span class="st {{ $r['stage'] }}">{{ $stLabel[$r['stage']] ?? $r['stage'] }}</span>@if ($r['paper'])<div class="by">توقيع ورقي</div>@endif</td>
            </tr>
          @empty
            <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:24px">لا توجد تقييمات لهذا الشهر.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection