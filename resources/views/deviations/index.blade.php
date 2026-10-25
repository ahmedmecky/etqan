@extends('layouts.app')

@section('title', 'سجل الانحرافات')

@push('head')
<style>
  .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:16px}
  .stat{background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px 14px}
  .stat b{display:block;font-size:26px;line-height:1.2}
  .stat span{font-size:13px;color:var(--muted)}
  .stat.bad b{color:var(--bad)} .stat.ok b{color:var(--ok)} .stat.warn b{color:var(--warn)}
  .filters{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:14px}
  .filters input[type=text]{max-width:240px}
  .filters select{max-width:180px}
  .seg{display:inline-flex;border:1px solid var(--line);border-radius:8px;overflow:hidden}
  .seg a{padding:7px 14px;font-weight:600;font-size:13.5px;color:var(--muted);text-decoration:none}
  .seg a+a{border-inline-start:1px solid var(--line)}
  .seg a.on{background:var(--brand);color:#fff}
  table.t td{vertical-align:top}
  tr.late td{background:#fff7f6}
  .code{font-weight:700;color:var(--brand);direction:ltr;display:inline-block;white-space:nowrap;font-size:13px}
  .by{font-size:11.5px;color:var(--muted)}
  .late-t{color:var(--bad);font-weight:700;font-size:12px;display:block}
  .cls{display:inline-block;border-radius:99px;padding:1px 10px;font-size:12px;font-weight:700}
  .cls.critical{background:#fbe7e5;color:#b3261e} .cls.major{background:#fdf1dc;color:#9a6300} .cls.minor{background:#e9eeec;color:#44524f}
  .st{font-size:12.5px;font-weight:600}
  .st.closed{color:var(--ok)}
  .what{max-width:340px}
  .repeats{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
  .repeats span{background:#fdf1dc;color:#9a6300;border-radius:99px;padding:3px 10px;font-size:12.5px;font-weight:600}
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
      <h1 class="page-title">سجل الانحرافات والإجراءات التصحيحية <span class="code" style="font-size:12px;color:var(--muted)">SH-008-F04</span></h1>
      <p class="page-sub" style="margin:0">{{ auth()->user()->tenant->name_ar }} · تُتابع المواعيد أسبوعياً، والمتأخر يُصعد لرئيس القسم ثم للمدير العام</p>
    </div>
    <div class="actions no-print">
      <a href="{{ $formUrl }}" class="btn">+ انحراف جديد</a>
      <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
    </div>
  </div>

  <div class="stats">
    <div class="stat warn"><b>{{ $stats['open'] }}</b><span>مفتوحة</span></div>
    <div class="stat bad"><b>{{ $stats['critical_open'] }}</b><span>حرجة مفتوحة</span></div>
    <div class="stat bad"><b>{{ $stats['late'] }}</b><span>متأخرة (خطة · إجراء · تحقق)</span></div>
    <div class="stat ok"><b>{{ $stats['closed'] }}</b><span>مغلقة بعد التحقق</span></div>
    <div class="stat ok"><b>{{ $stats['acts_on_time'] === null ? '—' : $stats['acts_on_time'] . '%' }}</b><span>إجراءات نُفذت في موعدها</span></div>
    <div class="stat"><b>{{ $stats['avg_close'] ?? '—' }}</b><span>متوسط أيام الإغلاق</span></div>
  </div>

  @if ($repeats->isNotEmpty())
    <div class="alert info no-print" style="margin-bottom:14px">
      <b>تكرار خلال آخر ١٢ شهراً (نفس القسم ونفس المصدر):</b> يُعرض في المراجعة الدورية، ويُراجع رفع التصنيف درجة.
      <div class="repeats">
        @foreach ($repeats as $k => $n)<span>{{ $k }} · {{ $n }} مرات</span>@endforeach
      </div>
    </div>
  @endif

  <div class="card">
    <div class="filters">
      @php($tabs = ['open' => 'المفتوحة', 'late' => 'المتأخرة', 'closed' => 'المغلقة', 'all' => 'الكل'])
      <div class="seg">
        @foreach ($tabs as $k => $label)
          <a href="{{ route('deviations.index', array_filter(array_merge($f, ['tab' => $k]))) }}" class="{{ $f['tab'] === $k ? 'on' : '' }}">{{ $label }}</a>
        @endforeach
      </div>
      <form method="GET" action="{{ route('deviations.index') }}" class="filters" style="margin:0">
        <input type="hidden" name="tab" value="{{ $f['tab'] }}">
        <input type="text" name="q" value="{{ $f['q'] }}" placeholder="بحث في الرقم أو الوصف أو السبب…">
        <select name="cls">
          <option value="">كل التصنيفات</option>
          @foreach ($classes as $k => $label)<option value="{{ $k }}" {{ $f['cls'] === $k ? 'selected' : '' }}>{{ $label }}</option>@endforeach
        </select>
        <select name="dept">
          <option value="">كل الأقسام</option>
          @foreach ($depts as $d)<option value="{{ $d }}" {{ $f['dept'] === $d ? 'selected' : '' }}>{{ $d }}</option>@endforeach
        </select>
        <select name="source">
          <option value="">كل المصادر</option>
          @foreach ($sources as $s)<option value="{{ $s }}" {{ $f['source'] === $s ? 'selected' : '' }}>{{ $s }}</option>@endforeach
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
          <tr>
            <th>الرقم</th><th>التاريخ</th><th>المصدر · القسم</th><th>الوصف المختصر</th><th>التصنيف</th>
            <th>موعد الخطة</th><th>الإجراءات</th><th>التحقق</th><th>المرحلة</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($rows as $r)
            <tr class="{{ $r['late'] ? 'late' : '' }}">
              <td><a href="{{ $formUrl }}?open={{ urlencode($r['number']) }}" class="code">{{ $r['number'] }}</a>
                @if ($r['ref'])<div class="by ltr">{{ $r['ref'] }}</div>@endif</td>
              <td><span class="ltr">{{ $r['date'] }}</span></td>
              <td>{{ $r['source'] ?: '—' }}<div class="by">{{ $r['dept'] }}</div></td>
              <td class="what">{{ \Illuminate\Support\Str::limit($r['what'], 120) }}
                @if ($r['root'])<div class="by">السبب: {{ \Illuminate\Support\Str::limit($r['root'], 90) }}</div>@endif</td>
              <td>@if ($r['cls'])<span class="cls {{ $r['cls'] }}">{{ $classes[$r['cls']] }}</span>@else — @endif</td>
              <td><span class="ltr">{{ $r['plan_due'] ?? '—' }}</span></td>
              <td>
                @if ($r['acts']) {{ $r['acts_done'] }} / {{ $r['acts'] }} منفذ @else — @endif
                @if ($r['next_due'])<div class="by">أقرب موعد: <span class="ltr">{{ $r['next_due'] }}</span></div>@endif
                @if ($r['round'] > 1)<div class="by">جولة تحقيق {{ $r['round'] }}</div>@endif
              </td>
              <td><span class="ltr">{{ $r['verify'] ?? '—' }}</span></td>
              <td>
                <span class="st {{ $r['stage'] }}">{{ $stages[$r['stage']] ?? $r['stage'] }}</span>
                @if ($r['closed_at'])<div class="by">أُغلق <span class="ltr">{{ $r['closed_at'] }}</span> · {{ $r['days_to_close'] }} يوم</div>@endif
                @foreach ($r['late_txt'] as $t)<span class="late-t">{{ $t }}</span>@endforeach
              </td>
            </tr>
          @empty
            <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:24px">لا توجد انحرافات مطابقة.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection