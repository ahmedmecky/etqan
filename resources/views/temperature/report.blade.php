@extends('layouts.app')

@section('title', 'تقرير الحرارة الشهري')

@push('head')
<style>
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:16px}
  .stat{background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px 14px}
  .stat b{display:block;font-size:26px;line-height:1.2;direction:ltr;text-align:right}
  .stat span{font-size:13px;color:var(--muted)}
  .stat.bad b{color:var(--bad)} .stat.ok b{color:var(--ok)} .stat.warn b{color:var(--warn)}
  .v{direction:ltr;font-variant-numeric:tabular-nums;white-space:nowrap}
  .bad{color:var(--bad);font-weight:700} .ok{color:var(--ok);font-weight:700} .wrn{color:#9a6300;font-weight:700}
  .by{font-size:11.5px;color:var(--muted)}
  .two{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  @media (max-width:900px){.two{grid-template-columns:1fr}}
  .two .card{margin:0 0 16px}
  .wk{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid var(--line);font-size:13.5px;flex-wrap:wrap}
  .wk:last-child{border:0}
  .wk form{display:flex;gap:6px;margin:0}
  .wk input[type=text]{width:180px;padding:4px 8px;font-size:12.5px}
  .miss{font-size:11.5px;color:var(--bad);max-width:300px}
  .pill{display:inline-block;border-radius:99px;padding:2px 10px;font-size:12px;font-weight:700;white-space:nowrap}
  .p-open{background:#fde8e6;color:var(--bad)} .p-pend{background:#fdf1dc;color:#9a6300} .p-done{background:#e3f4ea;color:var(--ok)}
  .err{color:var(--bad);font-size:13px;margin-top:6px}
  @media print{header.top,footer.foot,.no-print{display:none !important} main.wrap{padding:0;max-width:none} body{background:#fff;font-size:11px} *{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
</style>
@endpush

@section('content')
  @php
    $n = fn ($v) => \App\Models\TempPoint::n($v === null ? null : (float) $v);
    $totOut = $stats->sum('out');
    $totMiss = $stats->sum(fn ($s) => $s['missing']->count());
    $noF03 = $outs->filter(fn ($r) => ! $exc->has($r->id))->count();
    $weeksDue = collect($weeks)->filter(fn ($w) => $w['due'] && ! $w['review'])->count();
    $alarmDue = $cold->filter(fn ($p) => ! $alarms->has($p->id))->count();
  @endphp
  <div class="head-actions">
    <div>
      <h1 class="page-title">تقرير الحرارة والرطوبة الشهري</h1>
      <p class="page-sub" style="margin:0">{{ auth()->user()->tenant->name_ar }} · اتجاهات الحرارة والانحرافات والمراجعات · <span class="ltr">SH-030</span></p>
    </div>
    <div class="actions no-print">
      <form method="GET" action="{{ route('temperature.report') }}" style="margin:0"><input type="month" name="month" value="{{ $month }}" onchange="this.form.submit()" style="width:auto;direction:ltr"></form>
      <a href="{{ route('temperature.round') }}" class="btn">تسجيل القراءات</a>
      <a href="{{ route('temperature.sheet', ['month' => $month]) }}" class="btn ghost">سجل نصف الشهر</a>
      <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
    </div>
  </div>


  <div class="stats">
    <div class="stat {{ $totOut ? 'bad' : 'ok' }}"><b>{{ $totOut }}</b><span>قراءة خارج الحدود</span></div>
    <div class="stat {{ $noF03 ? 'bad' : '' }}"><b>{{ $noF03 }}</b><span>انحراف بدون تقرير F03</span></div>
    <div class="stat {{ $totMiss ? 'warn' : 'ok' }}"><b>{{ $totMiss }}</b><span>قراءة ناقصة (عدا الجمعة)</span></div>
    <div class="stat {{ $weeksDue ? 'warn' : '' }}"><b>{{ $weeksDue }}</b><span>أسبوع بدون مراجعة</span></div>
    <div class="stat {{ $alarmDue ? 'warn' : '' }}"><b>{{ $alarmDue }}</b><span>ثلاجة بدون اختبار إنذار هذا الشهر</span></div>
  </div>

  <div class="card">
    <h2 style="font-size:16px;margin:0 0 8px">نقاط المراقبة</h2>
    <div class="tbl-wrap">
      <table class="t">
        <thead><tr><th>النقطة</th><th>الحدود</th><th>القراءات</th><th>أعلى</th><th>أدنى</th><th>المتوسط</th><th>أعلى رطوبة</th><th>خارج الحدود</th><th>إنذار مبكر</th><th>الناقص</th></tr></thead>
        <tbody>
          @forelse ($stats as $s)
            @php($p = $s['p'])
            <tr>
              <td><a href="{{ route('temperature.sheet', ['point' => $p->id, 'month' => $month]) }}"><b>{{ $p->name }}</b></a><div class="by">{{ $p->typeLabel() }}{{ $p->is_active ? '' : ' · موقوفة' }}</div></td>
              <td class="by">{{ $p->limitsText() }}</td>
              <td class="v">{{ $s['count'] }} / {{ $s['expected'] }}</td>
              <td class="v {{ $s['max'] !== null && $s['max'] > $p->t_max ? 'bad' : '' }}">{{ $n($s['max']) ?: '—' }}</td>
              <td class="v {{ $s['min'] !== null && $s['min'] < $p->t_min ? 'bad' : '' }}">{{ $n($s['min']) ?: '—' }}</td>
              <td class="v">{{ $s['avg'] !== null ? $n(round($s['avg'], 1)) : '—' }}</td>
              <td class="v {{ $p->h_max && $s['humMax'] > $p->h_max ? 'bad' : '' }}">{{ $s['humMax'] !== null ? $s['humMax'] . '%' : '—' }}</td>
              <td class="v {{ $s['out'] ? 'bad' : '' }}">{{ $s['out'] }}</td>
              <td class="v {{ $s['warn'] ? 'wrn' : '' }}">{{ $s['warn'] }}</td>
              <td>
                @if ($s['missing']->isEmpty())<span class="ok">✓</span>
                @else
                  <div class="miss">{{ $s['missing']->count() }}: {{ $s['missing']->take(8)->map(fn ($m) => \Illuminate\Support\Carbon::parse($m[0])->format('d/m') . ($m[1] === 'am' ? ' ص' : ' م'))->implode('، ') }}{{ $s['missing']->count() > 8 ? '…' : '' }}</div>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="10" style="text-align:center;color:var(--muted);padding:20px">لا توجد نقاط مراقبة. <a href="{{ route('temperature.points') }}">إعداد النقاط</a></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <h2 style="font-size:16px;margin:0 0 8px">القراءات خارج الحدود ({{ $outs->count() }})</h2>
    <div class="tbl-wrap">
      <table class="t">
        <thead><tr><th>التاريخ</th><th>النقطة</th><th>الحالية / الأعلى / الأدنى</th><th>الرطوبة</th><th>الإجراء الفوري</th><th>تقرير الانحراف</th></tr></thead>
        <tbody>
          @forelse ($outs as $r)
            @php($x = $exc->get($r->id))
            <tr>
              <td class="v">{{ $r->date->format('Y-m-d') }} {{ $r->shift === 'am' ? 'ص' : 'م' }}</td>
              <td>{{ $r->point?->name }}</td>
              <td class="v">{{ $n($r->cur) }} / {{ $n($r->t_high) ?: '—' }} / {{ $n($r->t_low) ?: '—' }}</td>
              <td class="v">{{ $r->hum !== null ? $r->hum . '%' : '—' }}</td>
              <td class="by" style="max-width:320px">{{ $r->note }}</td>
              <td>
                @if ($x)<a href="{{ $f03Url }}?open={{ $x['id'] }}" class="pill {{ $x['final'] ? 'p-done' : 'p-pend' }}">{{ $x['no'] }} {{ $x['final'] ? '✓ مغلق' : '· مفتوح' }}</a>
                @else<a href="{{ $f03Url }}?reading={{ $r->id }}" class="pill p-open">⚠ فتح F03</a>@endif
              </td>
            </tr>
          @empty
            <tr><td colspan="6" style="text-align:center;color:var(--ok);padding:16px">لا توجد قراءات خارج الحدود هذا الشهر ✓</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="two">
    <div class="card">
      <h2 style="font-size:16px;margin:0 0 6px">المراجعة الأسبوعية لبيانات أجهزة التسجيل</h2>
      <div class="by" style="margin-bottom:6px">المدير الصيدلي يحمّل بيانات الأجهزة ويراجعها ويوقع أسبوعياً (الأسبوع من السبت للجمعة).</div>
      @foreach ($weeks as $w)
        <div class="wk">
          <span class="v">{{ $w['start']->format('d/m') }} – {{ $w['end']->format('d/m') }}</span>
          @if ($w['review'])
            <span class="ok">✓ {{ $w['review']->user?->displayName() }} <span class="by">{{ $w['review']->note }}</span></span>
          @elseif ($canReview && $w['start']->lte($today))
            <form method="POST" action="{{ route('temperature.checks') }}" class="no-print">
              @csrf
              <input type="hidden" name="kind" value="review">
              <input type="hidden" name="date" value="{{ $w['start']->lte($today) ? $w['start']->toDateString() : $today->toDateString() }}">
              <input type="text" name="note" placeholder="ملاحظات (اختياري)">
              <button type="submit" class="btn small">توقيع المراجعة</button>
            </form>
          @else
            <span class="{{ $w['due'] ? 'bad' : 'by' }}">{{ $w['due'] ? 'لم تُراجع' : 'لم يكتمل الأسبوع' }}</span>
          @endif
        </div>
      @endforeach
    </div>

    <div class="card">
      <h2 style="font-size:16px;margin:0 0 6px">اختبار الإنذار الشهري</h2>
      <div class="by" style="margin-bottom:6px">اختبار إنذار كل ثلاجة وغرفة تبريد شهرياً والتأكد من وصوله للمسؤولين.</div>
      @forelse ($cold as $p)
        @php($last = $alarms->get($p->id)?->first())
        <div class="wk">
          <span><b>{{ $p->name }}</b>
            @if ($last)<span class="{{ $last->result === 'ok' ? 'ok' : 'bad' }}"> · {{ $last->result === 'ok' ? 'يعمل' : 'لا يعمل' }}</span> <span class="by v">{{ $last->date->format('d/m') }} · {{ $last->user?->displayName() }}</span>
            @else <span class="bad"> · لم يُختبر</span>@endif
          </span>
          @if ($month === $today->format('Y-m'))
          <form method="POST" action="{{ route('temperature.checks') }}" class="no-print">
            @csrf
            <input type="hidden" name="kind" value="alarm">
            <input type="hidden" name="point_id" value="{{ $p->id }}">
            <input type="hidden" name="date" value="{{ $today->toDateString() }}">
            <select name="result" style="width:auto;padding:4px 6px;font-size:12.5px"><option value="ok">يعمل</option><option value="fail">لا يعمل</option></select>
            <input type="text" name="note" placeholder="ملاحظات / الإجراء">
            <button type="submit" class="btn small ghost">تسجيل</button>
          </form>
          @endif
        </div>
      @empty
        <div class="by">لا توجد ثلاجات أو غرف تبريد مسجلة.</div>
      @endforelse
    </div>
  </div>

  <div class="card">
    <h2 style="font-size:16px;margin:0 0 8px">تقارير انحراف الحرارة F03 ({{ $f03->count() }})</h2>
    <div class="tbl-wrap">
      <table class="t">
        <thead><tr><th>الرقم</th><th>التاريخ</th><th>النقطة</th><th>المدة</th><th>القرار</th><th>الانحراف SH-008</th><th>الحالة</th></tr></thead>
        <tbody>
          @php($stLabel = ['open' => 'لدى أمين المخزن', 'submitted' => 'بانتظار تقييم المدير الصيدلي', 'evaluated' => 'بانتظار اعتماد الجودة'])
          @forelse ($f03 as $rec)
            @php($d = $rec->data ?? [])
            <tr>
              <td><a href="{{ $f03Url }}?open={{ $rec->id }}" class="v"><b>{{ $rec->number }}</b></a></td>
              <td class="v">{{ $rec->record_date?->format('Y-m-d') }}</td>
              <td>{{ $d['pointName'] ?? '—' }}</td>
              <td class="v">{{ $d['durationText'] ?? '—' }}</td>
              <td class="by">{{ $d['decisionText'] ?? '—' }}</td>
              <td class="v">{{ $d['devNo'] ?? '' ?: '—' }}</td>
              <td>@if ($rec->status === 'final')<span class="pill p-done">مغلق</span>@else<span class="pill p-pend">{{ $stLabel[$d['stage'] ?? 'open'] ?? 'مسودة' }}</span>@endif</td>
            </tr>
          @empty
            <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:16px">لا توجد تقارير انحراف هذا الشهر.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection