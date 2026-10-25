@extends('layouts.app')

@section('title', 'تقرير الدورات والمحاضرين')

@push('head')
<style>
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:16px}
  .stat{background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px 14px}
  .stat b{display:block;font-size:26px;line-height:1.2;direction:ltr;text-align:right}
  .stat span{font-size:13px;color:var(--muted)}
  .stat.bad b{color:var(--bad)} .stat.ok b{color:var(--ok)} .stat.warn b{color:var(--warn)}
  .two{display:grid;grid-template-columns:1.4fr 1fr;gap:16px}
  @media (max-width:900px){.two{grid-template-columns:1fr}}
  .two .card{margin:0 0 16px}
  .v{direction:ltr;font-variant-numeric:tabular-nums;white-space:nowrap}
  .by{font-size:11.5px;color:var(--muted)}
  .pill{display:inline-block;border-radius:99px;padding:2px 10px;font-size:12px;font-weight:700;white-space:nowrap;background:#eef0f0;color:var(--muted)}
  .p-final{background:#e3f4ea;color:var(--ok)} .p-held,.p-approved{background:#fdf1dc;color:#9a6300} .p-late{background:#fde8e6;color:var(--bad)}
  .bar{display:flex;align-items:center;gap:8px;padding:4px 0;font-size:13px}
  .bar .l{width:120px} .bar .b{flex:1;height:12px;background:#eef0f0;border-radius:99px;overflow:hidden} .bar .b i{display:block;height:100%;background:#1b6e68}
  .bar b{width:28px;text-align:center}
  .months{display:grid;grid-template-columns:repeat(12,1fr);gap:4px;align-items:end;height:90px;margin-top:6px}
  .months div{background:#1b6e68;border-radius:4px 4px 0 0;min-height:2px;position:relative}
  .months span{position:absolute;top:-16px;left:0;right:0;text-align:center;font-size:11px;color:var(--muted)}
  .mlab{display:grid;grid-template-columns:repeat(12,1fr);gap:4px;font-size:10.5px;color:var(--muted);text-align:center;margin-top:3px}
  @media print{header.top,footer.foot,.no-print{display:none !important} main.wrap{padding:0;max-width:none} body{background:#fff;font-size:11px} *{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
</style>
@endpush

@section('content')
  @php
    $maxType = max(1, $byType->max('n'));
    $maxMon = max(1, $months->max());
    $mNames = ['ين', 'فب', 'مار', 'أبر', 'ماي', 'يون', 'يول', 'أغس', 'سبت', 'أكت', 'نوف', 'ديس'];
  @endphp
  <div class="head-actions">
    <div>
      <h1 class="page-title">تقرير الدورات والمحاضرين</h1>
      <p class="page-sub" style="margin:0">{{ auth()->user()->tenant->name_ar }} · التدريبات المنفذة ونتائجها وفاعليتها · <span class="ltr">SH-015</span></p>
    </div>
    <div class="actions no-print">
      <form method="GET" action="{{ route('training.index') }}" style="margin:0;display:flex;gap:6px">
        <select name="year" onchange="this.form.submit()" style="width:auto">@foreach ($years as $y)<option {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>@endforeach</select>
        <select name="type" onchange="this.form.submit()" style="width:auto"><option value="">كل الأنواع</option>@foreach ($types as $k => $t)<option value="{{ $k }}" {{ $type === $k ? 'selected' : '' }}>{{ $t }}</option>@endforeach</select>
      </form>
      <a href="{{ $formUrl }}" class="btn">+ تدريب جديد</a>
      <a href="{{ route('training.employee') }}" class="btn ghost">سجل تدريب موظف</a>
      <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
    </div>
  </div>

  <div class="stats">
    <div class="stat"><b>{{ $stats['sessions'] }}</b><span>تدريب منفذ في {{ $year }}</span></div>
    <div class="stat"><b>{{ rtrim(rtrim(number_format($stats['manHours'], 1), '0'), '.') }}</b><span>ساعة تدريب (× المتدربين)</span></div>
    <div class="stat"><b>{{ $stats['trainees'] }}</b><span>موظف تم تدريبه</span></div>
    <div class="stat {{ $stats['pass'] !== null && $stats['pass'] < 80 ? 'warn' : 'ok' }}"><b>{{ $stats['pass'] === null ? '—' : $stats['pass'] . '%' }}</b><span>نسبة اجتياز التقييم</span></div>
    <div class="stat {{ $stats['fail'] ? 'bad' : '' }}"><b>{{ $stats['fail'] }}</b><span>لم يجتز · يحتاج إعادة</span></div>
    <div class="stat {{ $stats['effLate'] ? 'bad' : '' }}"><b>{{ $stats['effLate'] }}</b><span>متابعة فاعلية متأخرة</span></div>
    <div class="stat"><b>{{ $stats['effective'] === null ? '—' : $stats['effective'] . '%' }}</b><span>تدريبات فعّالة (من المغلقة)</span></div>
  </div>

  <div class="two">
    <div class="card">
      <h2 style="font-size:16px;margin:0 0 8px">المحاضرون والجهات</h2>
      <div class="tbl-wrap">
        <table class="t">
          <thead><tr><th>المدرب / الجهة</th><th>تدريبات</th><th>ساعات</th><th>متدربون</th><th>الاجتياز</th><th>غير فعّال</th></tr></thead>
          <tbody>
            @forelse ($trainers as $t)
              <tr>
                <td><b>{{ $t['name'] }}</b><div class="by">{{ $t['job'] }}{{ $t['external'] ? ' · خارجي' : '' }}</div></td>
                <td class="v">{{ $t['sessions'] }}</td>
                <td class="v">{{ rtrim(rtrim(number_format($t['hours'], 1), '0'), '.') }}</td>
                <td class="v">{{ $t['trainees'] }}</td>
                <td class="v" style="{{ $t['pass'] !== null && $t['pass'] < 80 ? 'color:var(--bad);font-weight:700' : '' }}">{{ $t['pass'] === null ? '—' : $t['pass'] . '%' }}</td>
                <td class="v" style="{{ $t['notEff'] ? 'color:var(--bad);font-weight:700' : '' }}">{{ $t['notEff'] }}</td>
              </tr>
            @empty
              <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:18px">لا توجد تدريبات منفذة.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    <div class="card">
      <h2 style="font-size:16px;margin:0 0 8px">حسب النوع</h2>
      @foreach ($byType as $t)
        <div class="bar"><span class="l">{{ $t['label'] }}</span><span class="b"><i style="width:{{ round($t['n'] / $maxType * 100) }}%"></i></span><b>{{ $t['n'] }}</b></div>
      @endforeach
      <h2 style="font-size:16px;margin:16px 0 18px">حسب الشهر</h2>
      <div class="months">@foreach ($months as $m => $n)<div style="height:{{ round($n / $maxMon * 100) }}%">@if ($n)<span>{{ $n }}</span>@endif</div>@endforeach</div>
      <div class="mlab">@foreach ($mNames as $mn)<span>{{ $mn }}</span>@endforeach</div>
    </div>
  </div>

  <div class="card">
    <h2 style="font-size:16px;margin:0 0 8px">التدريبات ({{ $rows->count() }})</h2>
    <div class="tbl-wrap">
      <table class="t">
        <thead><tr><th>الرقم</th><th>التاريخ</th><th>الموضوع</th><th>النوع</th><th>السبب</th><th>المدرب</th><th>ساعات</th><th>حضر / اجتاز</th><th>الفاعلية</th><th>الحالة</th></tr></thead>
        <tbody>
          @forelse ($rows as $s)
            <tr>
              <td><a href="{{ $formUrl }}?open={{ $s['id'] }}" class="v"><b>{{ $s['number'] }}</b></a></td>
              <td class="v">{{ $s['date'] }}</td>
              <td>{{ $s['topic'] }}@if ($s['ref'])<div class="by">{{ $s['ref'] }}</div>@endif</td>
              <td class="by">{{ $types[$s['type']] ?? '—' }}</td>
              <td class="by v">{{ $s['triggerRef'] ?: '—' }}</td>
              <td>{{ $s['trainer'] ?: '—' }}</td>
              <td class="v">{{ rtrim(rtrim(number_format($s['hours'], 2), '0'), '.') }}</td>
              <td class="v">{{ $s['present'] }} / <span style="{{ $s['fail'] ? 'color:var(--bad);font-weight:700' : '' }}">{{ $s['pass'] }}</span></td>
              <td>
                @if ($s['eff'])<span class="pill {{ $s['eff'] === 'effective' ? 'p-final' : 'p-late' }}">{{ $effs[$s['eff']] }}</span>
                @elseif ($s['effDue'])<span class="pill {{ $s['effLate'] ? 'p-late' : '' }}">{{ $s['effLate'] ? 'متأخر · ' : '' }}<span class="v">{{ $s['effDue'] }}</span></span>
                @else — @endif
              </td>
              <td><span class="pill p-{{ $s['stage'] }}">{{ $stages[$s['stage']] ?? $s['stage'] }}</span></td>
            </tr>
          @empty
            <tr><td colspan="10" style="text-align:center;color:var(--muted);padding:20px">لا توجد تدريبات في {{ $year }}.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection