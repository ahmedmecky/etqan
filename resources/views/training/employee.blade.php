@extends('layouts.app')

@section('title', 'سجل تدريب الموظف')

@push('head')
<style>
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .v{direction:ltr;font-variant-numeric:tabular-nums;white-space:nowrap}
  .by{font-size:11.5px;color:var(--muted)}
  .pick{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
  .pick select{width:auto;min-width:260px}
  .meta{display:flex;gap:22px;flex-wrap:wrap;font-size:14px;margin-bottom:10px}
  .meta b{color:var(--brand)}
  .res-pass{color:var(--ok);font-weight:700} .res-fail{color:var(--bad);font-weight:700}
  .ph{display:none}
  .signs{display:none}
  @media print{
    header.top,footer.foot,.no-print{display:none !important} main.wrap{padding:0;max-width:none} body{background:#fff;font-size:11px}
    .card{border:0;padding:0}
    .ph{display:flex;justify-content:space-between;align-items:center;border-bottom:3px solid var(--gold);padding-bottom:6px;margin-bottom:8px}
    .signs{display:grid;grid-template-columns:1fr 1fr;gap:30px;margin-top:30px}
    .signs div{border-top:1px solid #555;padding-top:6px;text-align:center;font-size:12px}
    *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  }
</style>
@endpush

@section('content')
  <div class="head-actions no-print">
    <div>
      <h1 class="page-title">سجل تدريب الموظف</h1>
      <p class="page-sub" style="margin:0">كل التدريبات التي حضرها الموظف ونتائجها · <span class="ltr">SH-015-F05</span></p>
    </div>
    <div class="actions">
      <a href="{{ route('training.index') }}" class="btn ghost">تقرير الدورات والمحاضرين</a>
      @if ($emp || $name !== '')<button type="button" class="btn gold" onclick="window.print()">طباعة</button>@endif
    </div>
  </div>

  <div class="card no-print">
    <form method="GET" action="{{ route('training.employee') }}" class="pick">
      <select name="user" onchange="this.form.submit()">
        <option value="">— اختر الموظف —</option>
        @foreach ($users as $u)<option value="{{ $u->id }}" {{ $emp && $emp->id === $u->id ? 'selected' : '' }}>{{ $u->displayName() }}{{ $u->role ? ' · ' . $u->role->name_ar : '' }}</option>@endforeach
      </select>
      <span class="by">أو بالاسم (لمن ليس له حساب):</span>
      <input type="text" name="name" value="{{ $name }}" style="width:200px">
      <button type="submit" class="btn ghost small">عرض</button>
    </form>
  </div>

  @if ($emp || $name !== '')
    <div class="card">
      <div class="ph">
        <div><b style="font-size:16px;color:var(--brand)">{{ $tenant->name_ar }}</b><div style="font-size:10.5px;color:var(--muted)">إتقان · نظام إدارة الجودة</div></div>
        <div style="text-align:center"><b style="font-size:17px;color:var(--brand)">سجل تدريب الموظف</b><div class="v" style="font-size:11px">SH-015-F05</div></div>
        @if ($tenant->logoUrl())<img src="{{ $tenant->logoUrl() }}" alt="" style="height:40px">@else<span></span>@endif
      </div>
      <div class="meta">
        <span>الموظف: <b>{{ $emp ? $emp->displayName() : $name }}</b></span>
        @if ($emp)<span>الوظيفة: <b>{{ $emp->role?->name_ar ?? '—' }}</b></span>@endif
        <span>عدد التدريبات: <b>{{ $totals['n'] }}</b></span>
        <span>ساعات التدريب: <b class="v">{{ rtrim(rtrim(number_format($totals['hours'], 2), '0'), '.') }}</b></span>
        @if ($totals['fail'])<span style="color:var(--bad)">لم يجتز: <b style="color:var(--bad)">{{ $totals['fail'] }}</b></span>@endif
      </div>
      <div class="tbl-wrap">
        <table class="t">
          <thead><tr><th>م</th><th>التاريخ</th><th>رقم التدريب</th><th>الموضوع</th><th>المرجع</th><th>النوع</th><th>ساعات</th><th>المدرب</th><th>النتيجة</th><th>الحالة</th></tr></thead>
          <tbody>
            @forelse ($list as $i => $s)
              @php
                $r = $s['me']['result'] ?? '';
              @endphp
              <tr>
                <td>{{ $i + 1 }}</td>
                <td class="v">{{ $s['date'] }}</td>
                <td><a href="{{ $formUrl }}?open={{ $s['id'] }}" class="v">{{ $s['number'] }}</a></td>
                <td>{{ $s['topic'] }}</td>
                <td class="by">{{ $s['ref'] ?: '—' }}</td>
                <td class="by">{{ $types[$s['type']] ?? '—' }}</td>
                <td class="v">{{ rtrim(rtrim(number_format($s['hours'], 2), '0'), '.') }}</td>
                <td>{{ $s['trainer'] }}</td>
                <td class="{{ $r === 'pass' ? 'res-pass' : ($r === 'fail' ? 'res-fail' : '') }}">{{ ['pass' => 'اجتاز', 'fail' => 'لم يجتز · يحتاج إعادة', 'absent' => 'غائب'][$r] ?? '—' }}</td>
                <td class="by">{{ $stages[$s['stage']] ?? '' }}</td>
              </tr>
            @empty
              <tr><td colspan="10" style="text-align:center;color:var(--muted);padding:20px">لا توجد تدريبات مسجلة لهذا الموظف.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="signs"><div>مسؤول الموارد البشرية</div><div>المدير الصيدلي</div></div>
    </div>
  @endif
@endsection