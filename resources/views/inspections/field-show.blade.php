@extends('layouts.app')

@section('title', $r->number . ' · تفتيش ميداني')

@push('head')
<style>
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;font-size:14px}
  .meta span{display:block;font-size:12px;color:var(--muted)}
  .v{direction:ltr;font-variant-numeric:tabular-nums;white-space:nowrap}
  tr.bad td{background:#fff6f5}
  td.x{color:var(--bad);font-weight:700}
  .flag{display:inline-block;background:#fde8e6;color:var(--bad);border-radius:99px;padding:1px 9px;font-size:11.5px;font-weight:700;margin:1px}
  .okf{color:var(--ok);font-weight:700}
  .rev{background:#eef8f2;border-radius:10px;padding:12px 14px;font-size:14px}
  @media print{header.top,footer.foot,.no-print{display:none !important} main.wrap{padding:0;max-width:none} body{background:#fff} .card{border:0;padding:0} *{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
</style>
@endpush

@section('content')
  @php
    $items = $d['items'] ?? [];
    $fmt = fn ($ym) => $ym ? substr($ym, 5, 2) . '/' . substr($ym, 0, 4) : '';
  @endphp
  <div class="head-actions">
    <div>
      <h1 class="page-title">تفتيش ميداني <span class="v">{{ $r->number }}</span></h1>
      <p class="page-sub" style="margin:0">{{ auth()->user()->tenant->name_ar }} · <span class="ltr">SH-012-F04</span></p>
    </div>
    <div class="actions no-print">
      <a href="{{ route('field.index') }}" class="btn ghost">السجل</a>
      <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
    </div>
  </div>

  <div class="card">
    <div class="meta">
      <div><span>التاريخ والوقت</span><b class="v">{{ $d['date'] ?? '' }} {{ $d['time'] ?? '' }}</b></div>
      <div><span>المفتش (مفوض)</span><b>{{ $d['inspector'] ?? '' }}</b> {{ $d['job'] ?? '' }}</div>
      <div><span>رقم الفاتورة</span><b class="v">{{ $d['invoice'] ?? '' }}</b></div>
      <div><span>الصيدلية</span><b>{{ $d['pharmacy'] ?? '' }}</b> @if (! empty($d['pharmacyCode']))<span class="v" style="display:inline">({{ $d['pharmacyCode'] }})</span>@endif</div>
    </div>
  </div>

  <div class="card">
    <h2 style="font-size:16px;margin:0 0 8px">الأصناف ({{ count($items) }}) · غير مطابق {{ $d['bad'] ?? 0 }}</h2>
    <div class="tbl-wrap">
      <table class="t">
        <thead>
          <tr><th rowspan="2">م</th><th rowspan="2">الصنف</th><th rowspan="2">الزون</th><th colspan="2" style="text-align:center">التشغيلة</th><th colspan="2" style="text-align:center">الصلاحية</th><th rowspan="2">النتيجة</th><th rowspan="2">ملاحظات</th></tr>
          <tr><th>الفاتورة</th><th>الفعلي</th><th>الفاتورة</th><th>الفعلي</th></tr>
        </thead>
        <tbody>
          @foreach ($items as $i => $it)
            @php
              $iss = $it['issues'] ?? [];
            @endphp
            <tr class="{{ $iss ? 'bad' : '' }}">
              <td>{{ $i + 1 }}</td>
              <td>{{ $it['name'] ?? '' }}</td>
              <td class="v">{{ $it['zone'] ?? '' }}</td>
              <td class="v">{{ $it['batchInv'] ?? '' }}</td>
              <td class="v {{ in_array('التشغيلة مختلفة', $iss) ? 'x' : '' }}">{{ $it['batchReal'] ?? '' }}</td>
              <td class="v">{{ $fmt($it['expInv'] ?? '') }}</td>
              <td class="v {{ array_intersect(['الصلاحية مختلفة', 'منتهي الصلاحية'], $iss) ? 'x' : '' }}">{{ $fmt($it['expReal'] ?? '') }}</td>
              <td>@forelse ($iss as $f)<span class="flag">{{ $f }}</span>@empty<span class="okf">مطابق ✓</span>@endforelse</td>
              <td class="by">{{ $it['note'] ?? '' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @if (! empty($d['notes']))<p style="margin:10px 0 0"><b>ملاحظات:</b> {{ $d['notes'] }}</p>@endif
  </div>

  <div class="card">
    <h2 style="font-size:16px;margin:0 0 8px">مراجعة المدير الصيدلي</h2>
    @if ($r->status === 'final')
      <div class="rev">✓ راجعه <b>{{ $d['review']['by'] ?? '' }}</b> <span class="v">{{ $d['review']['at'] ?? '' }}</span>
        @if (! empty($d['review']['note']))<div style="margin-top:4px">{{ $d['review']['note'] }}</div>@endif
        @if (! empty($d['review']['devNo']))<div style="margin-top:4px">الانحراف: <a class="v" href="{{ route('forms.page', 'deviation') }}?open={{ urlencode($d['review']['devNo']) }}">{{ $d['review']['devNo'] }}</a></div>@endif
      </div>
    @elseif ($canManage)
      <form method="POST" action="{{ route('field.review', $r) }}" class="no-print">
        @csrf
        <label class="f {{ ($d['bad'] ?? 0) ? 'req' : '' }}">الإجراء المتخذ</label>
        <textarea name="note" placeholder="{{ ($d['bad'] ?? 0) ? 'تصحيح الفاتورة · إبلاغ العميل · سحب الصنف · تدريب المحضر…' : 'اختياري' }}">{{ old('note') }}</textarea>
        <div class="grid" style="margin-top:8px;max-width:520px">
          <div><label class="f">رقم الانحراف (SH-008)</label><input type="text" name="devNo" class="ltr-in" value="{{ old('devNo') }}" placeholder="DEV-…"></div>
        </div>
        <div class="actions" style="margin-top:10px">
          <button type="submit" class="btn">مراجعة وإغلاق</button>
          @if ($d['bad'] ?? 0)
            <a class="btn ghost" target="_blank" rel="noopener" href="{{ route('forms.page', 'deviation') }}?{{ http_build_query(['ref' => $r->number, 'source' => 'تفتيش ذاتي (SH-012)', 'sop' => 'SH-012 التفتيش الذاتي', 'what' => 'تفتيش ميداني ' . $r->number . ' · فاتورة ' . ($d['invoice'] ?? '') . ' · ' . ($d['pharmacy'] ?? '') . ': ' . collect($items)->filter(fn ($i) => ! empty($i['issues']))->map(fn ($i) => ($i['name'] ?? '') . ' (' . implode('، ', $i['issues']) . ')')->implode(' · ')]) }}">+ تسجيل انحراف</a>
          @endif
        </div>
      </form>
    @else
      <div class="by">بانتظار مراجعة المدير الصيدلي.</div>
    @endif
  </div>
@endsection