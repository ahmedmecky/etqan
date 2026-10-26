@extends('layouts.app')

@section('title', 'سجل الحرارة')

@push('head')
<style>
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:12px}
  .filters{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
  .filters select,.filters input{width:auto}
  .ph{display:none}
  table.s{width:100%;border-collapse:collapse;font-size:13px;background:#fff}
  table.s th,table.s td{border:1px solid #c9d3d1;padding:5px 6px;text-align:center}
  table.s th{background:#e3f0ee;color:var(--brand);font-weight:700;font-size:12px}
  table.s td.d{text-align:start;white-space:nowrap;font-weight:600}
  table.s td.v{direction:ltr;font-variant-numeric:tabular-nums}
  table.s td.bad{background:#fde8e6;color:var(--bad);font-weight:700}
  table.s td.warn{background:#fdf6e3;color:#9a6300}
  table.s tr.fri td{background:#f6f8f7;color:var(--muted)}
  table.s td.sg{font-size:11px;color:var(--muted)}
  .meta{display:flex;gap:18px;flex-wrap:wrap;font-size:13.5px;margin:6px 0 10px}
  .meta b{color:var(--brand)}
  .two{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px}
  @media (max-width:800px){.two{grid-template-columns:1fr}}
  .box{border:1px solid #c9d3d1;border-radius:8px;padding:10px 12px;background:#fff;font-size:13px}
  .box h3{margin:0 0 6px;font-size:14px;color:var(--brand)}
  .signs{display:grid;grid-template-columns:1fr 1fr;gap:30px;margin-top:28px}
  .signs div{border-top:1px solid #555;padding-top:6px;text-align:center;font-size:12.5px}
  .sfoot{margin-top:14px;font-size:10.5px;color:var(--muted);text-align:center;border-top:1px solid #c9d3d1;padding-top:4px}
  @page{size:A4 landscape;margin:10mm}
  @media print{
    header.top,footer.foot,.no-print,main.wrap>.alert{display:none !important}
    main.wrap{padding:0;max-width:none}
    body{background:#fff;font-size:11px}
    .card{border:0;padding:0;margin:0}
    .ph{display:flex;justify-content:space-between;align-items:center;border-bottom:3px solid var(--gold);padding-bottom:6px;margin-bottom:6px}
    table.s{font-size:10.5px}
    table.s th,table.s td{padding:3px 4px}
    *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  }
</style>
@endpush

@section('content')
  <div class="head-actions no-print">
    <div>
      <h1 class="page-title">سجل الحرارة لنصف الشهر</h1>
      <p class="page-sub" style="margin:0">صفحة لكل منطقة أو ثلاجة لكل نصف شهر · <span class="ltr">SH-030-F01 · F02</span></p>
    </div>
    <div class="actions">
      <a href="{{ route('temperature.round') }}" class="btn ghost">تسجيل القراءات</a>
      <a href="{{ route('temperature.report', ['month' => $month]) }}" class="btn ghost">التقرير الشهري</a>
      <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
    </div>
  </div>

  @if (! $point)
    <div class="card">لا توجد نقاط مراقبة. <a href="{{ route('temperature.points') }}">إعداد النقاط</a></div>
  @else
  <div class="card no-print">
    <form method="GET" action="{{ route('temperature.sheet') }}" class="filters">
      <select name="point" onchange="this.form.submit()">
        @foreach ($points as $pp)<option value="{{ $pp->id }}" {{ $pp->id === $point->id ? 'selected' : '' }}>{{ $pp->name }}{{ $pp->is_active ? '' : ' (موقوفة)' }}</option>@endforeach
      </select>
      <input type="month" name="month" value="{{ $month }}" onchange="this.form.submit()" style="direction:ltr">
      <select name="half" onchange="this.form.submit()">
        <option value="1" {{ $half === 1 ? 'selected' : '' }}>من ١ إلى ١٥</option>
        <option value="2" {{ $half === 2 ? 'selected' : '' }}>من ١٦ إلى نهاية الشهر</option>
      </select>
    </form>
  </div>

  @php
    $cold = $point->isCold();
    $hum = (bool) $point->h_max;
    $n = fn ($v) => \App\Models\TempPoint::n($v);
    $cls = function ($v) use ($point) {
        if ($v === null) return '';
        if ($v > $point->t_max || $v < $point->t_min) return 'bad';
        if (($point->warn_high !== null && $v > $point->warn_high) || ($point->warn_low !== null && $v < $point->warn_low)) return 'warn';
        return '';
    };
    $hcls = fn ($h) => $h !== null && $point->h_max && $h > $point->h_max ? 'bad' : '';
  @endphp

  <div class="card">
    <div class="ph">
      <div><b style="font-size:16px;color:var(--brand)">{{ $tenant->name_ar }}</b><div style="font-size:10.5px;color:var(--muted)">إتقان · نظام إدارة الجودة</div></div>
      <div style="text-align:center"><b style="font-size:17px;color:var(--brand)">{{ $cold ? 'سجل حرارة الثلاجات وغرف التبريد' : 'سجل الحرارة والرطوبة اليومي لمناطق التخزين' }}</b>
        <div class="ltr" style="font-size:11px">{{ $cold ? 'SH-030-F02' : 'SH-030-F01' }}</div></div>
      @if ($tenant->logoUrl())<img src="{{ $tenant->logoUrl() }}" alt="" style="height:40px">@else<span></span>@endif
    </div>
    <div class="meta">
      <span>{{ $cold ? 'الثلاجة' : 'المنطقة' }}: <b>{{ $point->name }}</b></span>
      <span>رقم الجهاز: <b class="ltr">{{ $point->device_no ?: '—' }}</b></span>
      <span>الحدود: <b>{{ $point->limitsText() }}</b></span>
      <span>الفترة: <b>{{ $half === 1 ? 'من ١ إلى ١٥' : 'من ١٦ إلى نهاية الشهر' }}</b> · الشهر: <b class="ltr">{{ $month }}</b></span>
    </div>

    <div class="tbl-wrap">
    <table class="s">
      <thead>
        <tr><th rowspan="2" style="width:84px">التاريخ</th>
          @foreach ($keys as $k)
            <th colspan="{{ $hum ? 3 : 2 }}">{{ \App\Support\TempRounds::label($k, null, false) }}@isset($times[$k])<div style="font-weight:400;font-size:10.5px">{{ $times[$k] }}</div>@endisset</th>
          @endforeach
          <th rowspan="2">ملاحظات / تقرير الانحراف</th><th rowspan="2" style="width:120px">المسجِّل</th></tr>
        <tr>
          @foreach ($keys as $k)
            <th>الوقت</th><th>°م</th>@if ($hum)<th>٪</th>@endif
          @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach ($days as $d)
          @php
            $row = $readings->get($d->toDateString(), collect());
            $fri = $d->dayOfWeek === \Illuminate\Support\Carbon::FRIDAY;
            $notes = $row->filter(fn ($r) => $r->note || $r->status === 'out');
          @endphp
          <tr class="{{ $fri && $row->isEmpty() ? 'fri' : '' }}">
            <td class="d">{{ $d->format('d/m') }} <span style="font-weight:400;font-size:11px">{{ ['أحد','اثنين','ثلاثاء','أربعاء','خميس','جمعة','سبت'][$d->dayOfWeek] }}</span></td>
            @foreach ($keys as $k)
              @php
                $r = $row->get($k);
              @endphp
              @if ($r)
                <td class="v">{{ substr((string) $r->time, 0, 5) }}</td>
                <td class="v {{ $cls($r->cur) }}">{{ $n($r->cur) }}</td>
                @if ($hum)<td class="v {{ $hcls($r->hum) }}">{{ $r->hum !== null ? $r->hum : '' }}</td>@endif
              @else
                <td colspan="{{ $hum ? 3 : 2 }}" style="color:var(--muted);font-size:11px">{{ $fri ? 'جمعة' : ($d->isFuture() ? '' : '—') }}</td>
              @endif
            @endforeach
            <td style="text-align:start;font-size:11.5px">
              @foreach ($notes as $r)
                @php
                  $x = $r->status === 'out' ? $exc->get($r->id) : null;
                @endphp
                <div>{{ \App\Support\TempRounds::short($r->shift) }}: {{ $r->note }}
                  @if ($r->status === 'out')<b style="color:var(--bad)" class="ltr">{{ $x ? $x['no'] : 'خارج الحدود' }}</b>@endif
                </div>
              @endforeach
            </td>
            <td class="sg">{{ $row->map(fn ($r) => $r->user?->displayName())->filter()->unique()->implode(' · ') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
    </div>

    <div class="two">
      <div class="box">
        <h3>المراجعة الأسبوعية لبيانات جهاز التسجيل (المدير الصيدلي)</h3>
        @forelse ($reviews as $c)
          <div>أسبوع <span class="ltr">{{ $c->date->format('d/m') }}–{{ $c->date->copy()->addDays(6)->format('d/m') }}</span>: ✓ {{ $c->user?->displayName() }} <span class="ltr" style="color:var(--muted)">{{ $c->created_at->timezone('Africa/Cairo')->format('Y-m-d') }}</span>@if ($c->note) · {{ $c->note }}@endif</div>
        @empty
          <div style="color:var(--muted)">لا توجد مراجعات مسجلة في هذه الفترة.</div>
        @endforelse
      </div>
      @if ($cold)
        <div class="box">
          <h3>اختبار الإنذار الشهري</h3>
          @forelse ($alarms as $a)
            <div><span class="ltr">{{ $a->date->format('Y-m-d') }}</span>: <b style="color:{{ $a->result === 'ok' ? 'var(--ok)' : 'var(--bad)' }}">{{ $a->result === 'ok' ? 'يعمل' : 'لا يعمل' }}</b> · {{ $a->user?->displayName() }}@if ($a->note) · {{ $a->note }}@endif</div>
          @empty
            <div style="color:var(--muted)">لم يُسجل اختبار إنذار هذا الشهر.</div>
          @endforelse
        </div>
      @endif
    </div>

    <div class="signs">
      <div>أمين المخزن<br>الاسم: ............ التوقيع: ............</div>
      <div>المدير الصيدلي<br>الاسم: ............ التوقيع: ............</div>
    </div>
    <div class="sfoot">{{ $tenant->name_ar }} · إتقان · {{ $cold ? 'SH-030-F02' : 'SH-030-F01' }} · القراءة خارج الحدود تُسجل ويُبلغ مدير الجودة والمدير الصيدلي · تقرير الانحراف SH-030-F03 · طُبع {{ now('Africa/Cairo')->format('Y-m-d H:i') }}</div>
  </div>
  @endif
@endsection