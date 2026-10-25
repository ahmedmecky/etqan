@extends('layouts.app')

@section('title', 'سجل الشكاوى')

@push('head')
<style>
  .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:16px}
  .stat{background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px 14px}
  .stat b{display:block;font-size:26px;line-height:1.2}
  .stat span{font-size:13px;color:var(--muted)}
  .stat.bad b{color:var(--bad)} .stat.ok b{color:var(--ok)} .stat.warn b{color:var(--warn)}
  .filters{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:14px}
  .filters input[type=text]{max-width:240px}
  .filters select{max-width:200px}
  .seg{display:inline-flex;border:1px solid var(--line);border-radius:8px;overflow:hidden}
  .seg a{padding:7px 14px;font-weight:600;font-size:13.5px;color:var(--muted);text-decoration:none}
  .seg a+a{border-inline-start:1px solid var(--line)}
  .seg a.on{background:var(--brand);color:#fff}
  table.t td{vertical-align:top}
  tr.late td{background:#fff7f6}
  .code{font-weight:700;color:var(--brand);direction:ltr;display:inline-block;white-space:nowrap;font-size:13px}
  .by{font-size:11.5px;color:var(--muted)}
  .late-t{color:var(--bad);font-weight:700;font-size:12px;display:block}
  .pr{display:inline-block;border-radius:99px;padding:1px 10px;font-size:12px;font-weight:700}
  .pr.critical{background:#fbe7e5;color:#b3261e} .pr.major{background:#fdf1dc;color:#9a6300} .pr.minor{background:#e9eeec;color:#44524f}
  .st{font-size:12.5px;font-weight:600} .st.closed{color:var(--ok)}
  .det{max-width:320px}
  .two{display:grid;grid-template-columns:1.6fr 1fr;gap:16px;margin-bottom:16px}
  @media (max-width:900px){.two{grid-template-columns:1fr}}
  table.tr{width:100%;border-collapse:collapse;font-size:13px}
  table.tr th,table.tr td{padding:6px 8px;border-bottom:1px solid var(--line);text-align:center}
  table.tr th:first-child,table.tr td:first-child{text-align:start}
  table.tr th{color:var(--muted);font-weight:600;font-size:12px;direction:ltr}
  table.tr td.z{color:#c3cbc9}
  table.tr tr.tot td{font-weight:700;border-top:2px solid var(--line)}
  .rep h3{font-size:13.5px;margin:10px 0 6px;color:var(--brand)}
  .rep span{display:inline-block;background:#fdf1dc;color:#9a6300;border-radius:99px;padding:2px 10px;font-size:12.5px;font-weight:600;margin:0 0 6px 4px}
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  @media print{
    header.top,footer.foot,.filters,.no-print{display:none !important}
    main.wrap{padding:0;max-width:none}
    .card{border:0;padding:0}
    body{background:#fff;font-size:11px}
    *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  }
</style>
@endpush

@section('content')
  <div class="head-actions">
    <div>
      <h1 class="page-title">سجل الشكاوى وتحليل الاتجاهات <span class="code" style="font-size:12px;color:var(--muted)">SH-007-F03</span></h1>
      <p class="page-sub" style="margin:0">{{ auth()->user()->tenant->name_ar }} · الشكوى المتجاوزة لمدتها تُصعّد للمدير العام · يُعرض التحليل شهرياً في مراجعة الإدارة</p>
    </div>
    <div class="actions no-print">
      <a href="{{ $formUrl }}" class="btn">+ شكوى جديدة</a>
      <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
    </div>
  </div>

  <div class="stats">
    <div class="stat warn"><b>{{ $stats['open'] }}</b><span>مفتوحة</span></div>
    <div class="stat bad"><b>{{ $stats['critical_open'] }}</b><span>حرجة مفتوحة</span></div>
    <div class="stat bad"><b>{{ $stats['late'] }}</b><span>متأخرة</span></div>
    <div class="stat ok"><b>{{ $stats['closed'] }}</b><span>مغلقة</span></div>
    <div class="stat ok"><b>{{ $stats['on_time'] === null ? '—' : $stats['on_time'] . '%' }}</b><span>أُغلقت في موعدها</span></div>
    <div class="stat {{ $stats['unsatisfied'] ? 'bad' : '' }}"><b>{{ $stats['unsatisfied'] }}</b><span>عميل غير راضٍ (تصعيد)</span></div>
    <a class="stat {{ $stats['portal_new'] ? 'warn' : '' }}" style="text-decoration:none;color:inherit" href="{{ route('complaints.index', ['tab' => 'open', 'src' => 'portal']) }}"><b>{{ $stats['portal_new'] }}</b><span>جديدة من رابط الصيدليات</span></a>
  </div>

  <div class="two">
    <div class="card" style="margin:0">
      <h2 style="font-size:15px;margin:0 0 8px">الشكاوى حسب النوع · آخر ٦ أشهر</h2>
      @if ($trend->isEmpty())
        <p class="by">لا توجد بيانات بعد.</p>
      @else
        <div class="tbl-wrap">
          <table class="tr">
            <tr><th>النوع</th>@foreach ($months as $m)<th>{{ $m }}</th>@endforeach</tr>
            @foreach ($trend as $t => $vals)
              <tr><td>{{ $t }}</td>@foreach ($vals as $n)<td class="{{ $n ? '' : 'z' }}">{{ $n }}</td>@endforeach</tr>
            @endforeach
            <tr class="tot"><td>الإجمالي</td>@foreach ($monthTotals as $n)<td>{{ $n }}</td>@endforeach</tr>
          </table>
        </div>
      @endif
    </div>
    <div class="card rep" style="margin:0">
      <h2 style="font-size:15px;margin:0 0 4px">التكرار خلال ١٢ شهراً</h2>
      <p class="by" style="margin:0">التكرار من نفس العميل أو عن نفس الصنف أو القسم يحتاج إجراءً تصحيحياً رسمياً (SH-008).</p>
      @php($any = false)
      @foreach ($repeats as $label => $items)
        @if ($items->isNotEmpty())
          @php($any = true)
          <h3>{{ $label }}</h3>
          @foreach ($items as $k => $n)<span>{{ $k }} · {{ $n }}</span>@endforeach
        @endif
      @endforeach
      @if (! $any)<p class="by" style="margin-top:10px">لا يوجد تكرار.</p>@endif
    </div>
  </div>

  <div class="card">
    <div class="filters">
      @php($tabs = ['open' => 'المفتوحة', 'late' => 'المتأخرة', 'closed' => 'المغلقة', 'all' => 'الكل'])
      <div class="seg">
        @foreach ($tabs as $k => $label)
          <a href="{{ route('complaints.index', array_filter(array_merge($f, ['tab' => $k]))) }}" class="{{ $f['tab'] === $k ? 'on' : '' }}">{{ $label }}</a>
        @endforeach
      </div>
      <form method="GET" action="{{ route('complaints.index') }}" class="filters" style="margin:0">
        <input type="hidden" name="tab" value="{{ $f['tab'] }}">
        <input type="text" name="q" value="{{ $f['q'] }}" placeholder="بحث في الرقم أو العميل أو الصنف…">
        <select name="prio">
          <option value="">كل الأولويات</option>
          @foreach ($priorities as $k => $label)<option value="{{ $k }}" {{ $f['prio'] === $k ? 'selected' : '' }}>{{ $label }}</option>@endforeach
        </select>
        <select name="type">
          <option value="">كل الأنواع</option>
          @foreach ($types as $t)<option value="{{ $t }}" {{ $f['type'] === $t ? 'selected' : '' }}>{{ $t }}</option>@endforeach
        </select>
        <select name="src">
          <option value="">كل المصادر</option>
          <option value="portal" {{ $f['src'] === 'portal' ? 'selected' : '' }}>رابط الصيدليات</option>
          <option value="staff" {{ $f['src'] === 'staff' ? 'selected' : '' }}>مسجلة بواسطة الموظفين</option>
        </select>
        <select name="year">
          <option value="">كل السنوات</option>
          @foreach ($years as $y)<option value="{{ $y }}" {{ $f['year'] === $y ? 'selected' : '' }}>{{ $y }}</option>@endforeach
        </select>
        <button type="submit" class="btn ghost">بحث</button>
      </form>
    </div>

    <div class="tbl-wrap">
      <table class="t">
        <thead>
          <tr><th>الرقم</th><th>التاريخ</th><th>العميل</th><th>النوع · الأولوية</th><th>الشكوى · الصنف</th><th>المسؤول</th><th>موعد الإغلاق</th><th>الحالة</th></tr>
        </thead>
        <tbody>
          @forelse ($rows as $r)
            <tr class="{{ $r['late'] ? 'late' : '' }}">
              <td><a href="{{ $formUrl }}?open={{ urlencode($r['number']) }}" class="code">{{ $r['number'] }}</a>
                @if ($r['dev'])<div class="by ltr">{{ $r['dev'] }}</div>@endif</td>
              <td><span class="ltr">{{ $r['date'] }}</span></td>
              <td>{{ $r['customer'] }}<div class="by">{{ $r['cust_type'] }}@if ($r['mobile']) · <span class="ltr">{{ $r['mobile'] }}</span>@endif</div>
                @if ($r['portal'])<span class="pr" style="background:#e3f0ee;color:#0f5e5a">رابط الصيدليات</span>@endif</td>
              <td>{{ $r['type'] ?: '—' }}@if ($r['prio'])<div><span class="pr {{ $r['prio'] }}">{{ $priorities[$r['prio']] }}</span></div>@endif</td>
              <td class="det">{{ \Illuminate\Support\Str::limit($r['details'], 110) }}
                @if ($r['products'])<div class="by">{{ $r['products'] }}</div>@endif</td>
              <td>{{ $r['owner'] ?: '—' }}<div class="by">{{ $r['dept'] }}</div></td>
              <td><span class="ltr">{{ $r['due'] ?? '—' }}</span></td>
              <td>
                <span class="st {{ $r['stage'] }}">{{ $stages[$r['stage']] ?? $r['stage'] }}</span>
                @if ($r['closed_at'])<div class="by">أُغلقت <span class="ltr">{{ $r['closed_at'] }}</span>{{ $r['sat'] === 'N' ? ' · غير راضٍ' : '' }}</div>@endif
                @foreach ($r['late_txt'] as $t)<span class="late-t">{{ $t }}</span>@endforeach
              </td>
            </tr>
          @empty
            <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:24px">لا توجد شكاوى مطابقة.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection