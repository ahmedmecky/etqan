@extends('layouts.app')

@section('title', 'متابعة القرارات')

@push('head')
<style>
  .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:16px}
  .stat{background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px 14px}
  .stat b{display:block;font-size:26px;line-height:1.2}
  .stat span{font-size:13px;color:var(--muted)}
  .stat.bad b{color:var(--bad)} .stat.ok b{color:var(--ok)} .stat.warn b{color:var(--warn)}
  .filters{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:14px}
  .filters input[type=text]{max-width:280px}
  .filters select{max-width:200px}
  .seg{display:inline-flex;border:1px solid var(--line);border-radius:8px;overflow:hidden}
  .seg a{padding:7px 14px;font-weight:600;font-size:13.5px;color:var(--muted);text-decoration:none}
  .seg a+a{border-inline-start:1px solid var(--line)}
  .seg a.on{background:var(--brand);color:#fff}
  table.t td{vertical-align:top}
  tr.overdue td{background:#fff7f6}
  tr.escalate td{background:#fdecea}
  tr.done td{color:#5f6f6c}
  .code{font-weight:700;color:var(--brand);direction:ltr;display:inline-block;white-space:nowrap;font-size:13px}
  .late{color:var(--bad);font-weight:700;font-size:12.5px}
  .esc{display:inline-block;background:var(--bad);color:#fff;border-radius:99px;font-size:11px;font-weight:700;padding:1px 8px;margin-top:2px}
  .upd{display:grid;grid-template-columns:110px 130px 1fr auto;gap:6px;align-items:center;min-width:430px}
  .upd select,.upd input{padding:5px 7px;font-size:13px}
  .upd .btn{padding:5px 12px;font-size:13px}
  .by{font-size:11.5px;color:var(--muted)}
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  @media print{
    header.top,footer.foot,.filters,.no-print,.upd .btn{display:none !important}
    main.wrap{padding:0;max-width:none}
    .card{border:0;padding:0}
    body{background:#fff;font-size:11.5px}
    .upd{display:block;min-width:0}
    .upd select,.upd input{border:0;padding:0;background:none;appearance:none;-webkit-appearance:none}
    *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  }
</style>
@endpush

@section('content')
  <div class="head-actions">
    <div>
      <h1 class="page-title">سجل متابعة القرارات <span class="code" style="font-size:12px;color:var(--muted)">SH-034-F03</span></h1>
      <p class="page-sub" style="margin:0">كل القرارات من محاضر الاجتماعات المعتمدة · {{ auth()->user()->tenant->name_ar }}</p>
    </div>
    <div class="actions no-print">
      <a href="{{ route('forms.page', 'meeting-minutes') }}" class="btn ghost">محاضر الاجتماعات</a>
      <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
    </div>
  </div>

  <div class="stats">
    <div class="stat"><b>{{ $stats['total'] }}</b><span>إجمالي القرارات</span></div>
    <div class="stat warn"><b>{{ $stats['open'] }}</b><span>مفتوحة</span></div>
    <div class="stat bad"><b>{{ $stats['overdue'] }}</b><span>متأخرة عن موعدها</span></div>
    <div class="stat bad"><b>{{ $stats['escalate'] }}</b><span>متأخرة أكثر من {{ $escalateDays }} يوماً (تصعيد)</span></div>
    <div class="stat ok"><b>{{ $stats['done'] }}</b><span>منفذة</span></div>
    <div class="stat ok"><b>{{ $stats['on_time'] === null ? '—' : $stats['on_time'] . '%' }}</b><span>نُفذت في موعدها</span></div>
  </div>

  <div class="card">
    <div class="filters">
      @php($tabs = ['open' => 'المفتوحة', 'overdue' => 'المتأخرة', 'done' => 'المنفذة', 'cancelled' => 'الملغاة', 'all' => 'الكل'])
      <div class="seg">
        @foreach ($tabs as $k => $label)
          <a href="{{ route('decisions.index', array_filter(['status' => $k, 'q' => $q, 'type' => $type])) }}" class="{{ $status === $k ? 'on' : '' }}">{{ $label }}</a>
        @endforeach
      </div>
      <form method="GET" action="{{ route('decisions.index') }}" class="filters" style="margin:0">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="text" name="q" value="{{ $q }}" placeholder="بحث في القرار أو المسؤول أو رقم المحضر…">
        <select name="type">
          <option value="">كل أنواع الاجتماعات</option>
          @foreach ($types as $t)
            <option value="{{ $t }}" {{ $type === $t ? 'selected' : '' }}>{{ $t }}</option>
          @endforeach
        </select>
        <button type="submit" class="btn ghost">بحث</button>
      </form>
    </div>

    @if (! $canEdit)
      <div class="alert info no-print">تحديث حالة القرارات يتم بواسطة مدير المخزن أو مدير إدارة الجودة.</div>
    @endif

    <div class="tbl-wrap">
      <table class="t">
        <thead>
          <tr>
            <th>رقم القرار</th>
            <th style="min-width:260px">القرار</th>
            <th>المسؤول</th>
            <th>الاجتماع</th>
            <th>الموعد</th>
            <th>{{ $canEdit ? 'الحالة · تاريخ التنفيذ · الدليل' : 'الحالة' }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($rows as $r)
            <tr class="{{ $r['escalate'] ? 'escalate' : ($r['overdue'] ? 'overdue' : ($r['status'] !== 'open' ? 'done' : '')) }}">
              <td><span class="code">{{ $r['code'] }}</span></td>
              <td>{{ $r['text'] }}@if ($r['item'])<div class="by">بند {{ $r['item'] }}</div>@endif</td>
              <td>{{ $r['owner'] }}</td>
              <td><div>{{ $r['type'] }}</div><div class="by"><span class="ltr">{{ $r['date'] }}</span></div></td>
              <td>
                <span class="ltr">{{ $r['due'] ?? '—' }}</span>
                @if ($r['overdue'])
                  <div class="late">متأخر {{ $r['late_days'] }} يوم</div>
                  @if ($r['escalate'])<span class="esc">تصعيد</span>@endif
                @endif
              </td>
              <td>
                @if ($canEdit)
                  <form method="POST" action="{{ route('decisions.update', [$r['record_id'], $r['key']]) }}" class="upd">
                    @csrf
                    @method('PUT')
                    <select name="status">
                      @foreach ($labels as $k => $label)
                        <option value="{{ $k }}" {{ $r['status'] === $k ? 'selected' : '' }}>{{ $label }}</option>
                      @endforeach
                    </select>
                    <input type="date" name="done_at" value="{{ $r['done_at'] }}" title="تاريخ التنفيذ">
                    <input type="text" name="note" value="{{ $r['note'] }}" placeholder="الدليل / ملاحظة">
                    <button type="submit" class="btn ghost">حفظ</button>
                  </form>
                @else
                  <span class="badge {{ $r['status'] === 'done' ? 'b-ok' : ($r['status'] === 'cancelled' ? 'b-mute' : ($r['overdue'] ? 'b-bad' : 'b-mute')) }}">{{ $labels[$r['status']] }}</span>
                  @if ($r['done_at'])<div class="by">نُفذ: <span class="ltr">{{ $r['done_at'] }}</span></div>@endif
                  @if ($r['note'])<div class="by">{{ $r['note'] }}</div>@endif
                @endif
                @if ($r['by'])<div class="by">آخر تحديث: {{ $r['by'] }}</div>@endif
              </td>
            </tr>
          @empty
            <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px">
              لا توجد قرارات مطابقة. القرارات تظهر هنا بعد اعتماد محضر الاجتماع.
            </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection