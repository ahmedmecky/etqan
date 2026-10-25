@extends('layouts.app')

@section('title', 'سجل التفتيش الذاتي')

@push('head')
<style>
  .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:16px}
  .stat{background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px 14px}
  .stat b{display:block;font-size:26px;line-height:1.2}
  .stat span{font-size:13px;color:var(--muted)}
  .stat.bad b{color:var(--bad)} .stat.ok b{color:var(--ok)} .stat.warn b{color:var(--warn)}
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .code{font-weight:700;color:var(--brand);direction:ltr;display:inline-block;white-space:nowrap;font-size:13px}
  .by{font-size:11.5px;color:var(--muted)}
  table.cov{width:100%;border-collapse:collapse;font-size:13px}
  table.cov th,table.cov td{border-bottom:1px solid var(--line);padding:6px 5px;text-align:center;vertical-align:middle}
  table.cov th{color:var(--muted);font-weight:600;font-size:12px;background:#fafbfb}
  table.cov td.p,table.cov th.p{text-align:start}
  table.cov td.m{width:28px;padding:4px 2px}
  .dot{display:inline-block;width:14px;height:14px;border-radius:50%;background:var(--brand)}
  tr.od td{background:#fff7f6}
  .hr{display:inline-block;background:#fdf1dc;color:#9a6300;border-radius:99px;font-size:11px;font-weight:700;padding:0 7px;margin-inline-start:4px}
  .late{color:var(--bad);font-weight:700;font-size:12px}
  .sc{font-weight:700;direction:ltr;display:inline-block}
  .sc.low{color:var(--bad)} .sc.ok{color:var(--ok)}
  .tag{display:inline-block;border-radius:99px;padding:1px 8px;font-size:11.5px;font-weight:700;margin:1px}
  .tag.c{background:#fbe7e5;color:#b3261e} .tag.j{background:#fdf1dc;color:#9a6300} .tag.n{background:#e9eeec;color:#44524f} .tag.o{background:#e3f0ee;color:#0f5e5a}
  .rep span{display:block;font-size:13px;padding:4px 0;border-bottom:1px dashed var(--line)}
  .rep b{color:#9a6300}
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
  @php($types = ['planned' => 'مخطط', 'surprise' => 'مفاجئ', 'followup' => 'متابعة'])
  <div class="head-actions">
    <div>
      <h1 class="page-title">سجل التفتيش الذاتي والتغطية السنوية <span class="code" style="font-size:12px;color:var(--muted)">SH-012-F01 · F03</span></h1>
      <p class="page-sub" style="margin:0">{{ auth()->user()->tenant->name_ar }} · كل إجراء مرة سنوياً على الأقل، والعمليات عالية الخطورة كل ٦ أشهر</p>
    </div>
    <div class="actions no-print">
      <form method="GET" action="{{ route('inspections.index') }}" style="margin:0">
        <select name="year" onchange="this.form.submit()" style="width:auto">
          @foreach ($years as $y)<option value="{{ $y }}" {{ $y === $year ? 'selected' : '' }}>{{ $y }}</option>@endforeach
        </select>
      </form>
      <a href="{{ $formUrl }}" class="btn">+ تفتيش جديد</a>
      <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
    </div>
  </div>

  <div class="stats">
    <div class="stat"><b>{{ $stats['count'] }}</b><span>تفتيش معتمد في {{ $year }}</span></div>
    <div class="stat {{ $stats['avg'] !== null && $stats['avg'] < 80 ? 'bad' : 'ok' }}"><b>{{ $stats['avg'] === null ? '—' : $stats['avg'] . '%' }}</b><span>متوسط نسبة المطابقة</span></div>
    <div class="stat {{ $stats['crit'] ? 'bad' : '' }}"><b>{{ $stats['crit'] }}</b><span>حالات حرجة في {{ $year }}</span></div>
    <div class="stat {{ $stats['open'] ? 'warn' : 'ok' }}"><b>{{ $stats['open'] }}</b><span>عدم مطابقة لم يُغلق انحرافها</span></div>
    <div class="stat {{ $stats['covered'] < $stats['total'] ? 'warn' : 'ok' }}"><b>{{ $stats['covered'] }} / {{ $stats['total'] }}</b><span>إجراءات في موعد التفتيش</span></div>
    <div class="stat"><b>{{ $stats['drafts'] }}</b><span>تفتيشات لم يصدر تقريرها</span></div>
  </div>

  <div class="card">
    <h2 style="font-size:16px;margin:0 0 4px">التغطية السنوية · {{ $year }}</h2>
    <p class="by" style="margin:0 0 10px">● تفتيش معتمد في الشهر. الصف الأحمر: لم يُفتش عليه أو تجاوز موعده. اضغط على كود الإجراء لبدء تفتيش عليه.</p>
    <div class="tbl-wrap">
      <table class="cov">
        <thead><tr>
          <th class="p">الإجراء</th>
          @foreach (['ينا', 'فبر', 'مار', 'أبر', 'ماي', 'يون', 'يول', 'أغس', 'سبت', 'أكت', 'نوف', 'ديس'] as $m)<th>{{ $m }}</th>@endforeach
          <th>آخر تفتيش</th><th>المطابقة</th><th>الموعد التالي</th>
        </tr></thead>
        <tbody>
          @foreach ($coverage as $c)
            <tr class="{{ $c['overdue'] ? 'od' : '' }}">
              <td class="p"><a href="{{ $formUrl }}?process={{ $c['code'] }}" class="code">{{ $c['code'] }}</a> {{ $c['title'] }}@if ($c['high'])<span class="hr">٦ أشهر</span>@endif</td>
              @for ($m = 1; $m <= 12; $m++)
                <td class="m">@if (in_array($m, $c['months']))<span class="dot"></span>@endif</td>
              @endfor
              <td>@if ($c['last'])<a href="{{ $formUrl }}?open={{ urlencode($c['number']) }}" class="ltr">{{ $c['last'] }}</a>@else — @endif</td>
              <td>@if ($c['score'] !== null)<span class="sc {{ $c['score'] < 80 ? 'low' : 'ok' }}">{{ $c['score'] }}%</span>@else — @endif</td>
              <td>@if ($c['next'])<span class="ltr {{ $c['overdue'] ? 'late' : '' }}">{{ $c['next'] }}</span>@else<span class="late">لم يُفتش</span>@endif</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  @if ($repeats->isNotEmpty())
    <div class="card rep">
      <h2 style="font-size:16px;margin:0 0 6px">عدم مطابقة متكررة</h2>
      <p class="by" style="margin:0 0 6px">نفس البند غير مطابق في أكثر من تفتيش: يُرفع تصنيفه درجة ويُعاد التحقيق في السبب الجذري، ويزيد تكرار التفتيش على العملية.</p>
      @foreach ($repeats as $k => $n)<span>{{ $k }} · <b>{{ $n }} مرات</b></span>@endforeach
    </div>
  @endif

  <div class="card">
    <h2 style="font-size:16px;margin:0 0 10px">التفتيشات</h2>
    <div class="tbl-wrap">
      <table class="t">
        <thead><tr><th>الرقم</th><th>التاريخ</th><th>الإجراء</th><th>النوع · القسم</th><th>المفتش</th><th>المطابقة</th><th>النتائج</th><th>المتابعة</th></tr></thead>
        <tbody>
          @forelse ($rows as $r)
            <tr>
              <td><a href="{{ $formUrl }}?open={{ urlencode($r['number']) }}" class="code">{{ $r['number'] }}</a>
                <div class="by">{{ $r['final'] ? 'تقرير معتمد' : 'مسودة' }}</div></td>
              <td><span class="ltr">{{ $r['date'] }}</span></td>
              <td><span class="code">{{ $r['process'] }}</span> <div class="by">{{ $r['title'] }}</div></td>
              <td>{{ $types[$r['type']] ?? '' }}<div class="by">{{ $r['dept'] }}</div></td>
              <td>{{ $r['lead'] }}</td>
              <td>@if ($r['score'] !== null)<span class="sc {{ $r['score'] < 80 ? 'low' : 'ok' }}">{{ $r['score'] }}%</span>@else — @endif</td>
              <td>
                @if ($r['crit'])<span class="tag c">حرجة {{ $r['crit'] }}</span>@endif
                @if ($r['maj'])<span class="tag j">رئيسية {{ $r['maj'] }}</span>@endif
                @if ($r['min'])<span class="tag n">ثانوية {{ $r['min'] }}</span>@endif
                @if ($r['opp'])<span class="tag o">تحسين {{ $r['opp'] }}</span>@endif
                @if (! $r['crit'] && ! $r['maj'] && ! $r['min'] && ! $r['opp'])<span class="by">—</span>@endif
                @if ($r['devs'])<div class="by ltr">{{ implode(' · ', $r['devs']) }}</div>@endif
              </td>
              <td>
                @if ($r['open'])<span class="late">{{ $r['open'] }} مفتوحة</span>@elseif ($r['final'] && ($r['crit'] + $r['maj'] + $r['min']))<span class="by" style="color:var(--ok);font-weight:700">مغلقة</span>@endif
                @if ($r['follow'])<div class="by">تفتيش متابعة: <span class="ltr {{ $r['follow'] < $today ? 'late' : '' }}">{{ $r['follow'] }}</span></div>@endif
              </td>
            </tr>
          @empty
            <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:24px">لا توجد تفتيشات بعد.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection