@extends('layouts.app')

@section('title', 'تقرير المراجعة · ' . $a->number)

@push('head')
  @include('documents._print-style')
  <style>
    .pd table.t2 td,.pd table.t2 th{font-size:11.5px}
    .pd .gr{display:inline-block;font-size:10.5px;font-weight:700;border-radius:5px;padding:0 6px;color:#fff;white-space:nowrap}
    .pd .pre{white-space:pre-line}
  </style>
@endpush

@section('content')
  @php
    $logo = $tenant->logoUrl();
    $G = \App\Support\Audits::GRADES;
    $groups = $a->areas->map(fn ($ar) => [$ar->name, $ar, $ar->findings])->all();
    $general = $a->findings->whereNull('area_id');
    if ($general->isNotEmpty()) {
        $groups[] = ['ملاحظات عامة', null, $general];
    }
  @endphp

  <div class="ptools">
    <div class="fl"><a href="{{ route('audits.show', $a) }}" style="border:0;padding:0;font-size:13.5px">← {{ $a->number }}</a>
      @if ($a->inField())<span class="warn">مسودة: لم يُرسل التقرير بعد</span>@endif</div>
    <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
  </div>

  <div class="pd">
    @if ($a->inField())<div class="wm"><span>مسودة</span></div>@endif
    <div class="hd">
      <div class="tn">
        @if ($logo)<img src="{{ $logo }}" alt="">@endif
        <div><b>{{ $tenant->name_ar }}</b><small>{{ $tenant->name_en }}</small><small>نظام إدارة الجودة · إتقان</small></div>
      </div>
      <div class="tt"><span>{{ $a->typeLabel() }}</span><b>تقرير المراجعة</b><small>{{ $a->number }} · SH-012 · SH-025</small></div>
      <div class="mt">الحالة: <b>{{ $a->statusLabel() }}</b><br>التقييم: <b>{{ \App\Support\Audits::RATINGS[$a->rating] ?? '—' }}</b></div>
    </div>

    <table class="dates">
      <tr><td class="k">العنوان</td><td colspan="3"><b>{{ $a->title }}</b></td></tr>
      <tr><td class="k">الجهة</td><td>{{ $a->company ?: '—' }}</td><td class="k">رئيس الفريق</td><td>{{ $a->lead ?: '—' }}{{ $a->team ? ' · ' . $a->team : '' }}</td></tr>
      <tr><td class="k">الفترة</td><td class="ltr" style="text-align:right">{{ $a->start_date->format('Y-m-d') }} ← {{ $a->end_date->format('Y-m-d') }}</td><td class="k">تاريخ التقرير</td><td class="ltr" style="text-align:right">{{ $a->reported_at?->timezone('Africa/Cairo')->format('Y-m-d') ?? '—' }}</td></tr>
      @if ($a->scope)<tr><td class="k">النطاق</td><td colspan="3">{{ $a->scope }}</td></tr>@endif
      <tr><td class="k">المعايير</td><td colspan="3">{{ $a->criteria ?: '—' }}</td></tr>
    </table>

    <h3><i>١.</i>ملخص الملاحظات</h3>
    <table class="t2">
      <tr><th>الإدارة</th>@foreach ($G as $g)<th class="c">{{ $g[0] }}</th>@endforeach</tr>
      @foreach ($groups as [$name, $ar, $list])
        <tr><td>{{ $name }}</td>@foreach ($G as $k => $g)<td class="c">{{ $list->where('grade', $k)->count() ?: '' }}</td>@endforeach</tr>
      @endforeach
      <tr class="g"><td>الإجمالي</td>@foreach ($G as $k => $g)<td class="c">{{ $s['grades'][$k] }}</td>@endforeach</tr>
    </table>

    <h3><i>٢.</i>الخلاصة</h3>
    <p class="pre">{{ $a->conclusion ?: '—' }}</p>

    <h3><i>٣.</i>تفاصيل المراجعة والملاحظات</h3>
    @foreach ($groups as [$name, $ar, $list])
      <h4>{{ $name }} @if ($ar && $ar->sops)<span class="mut ltr" style="font-weight:400">{{ $ar->sops }}</span>@endif</h4>
      @if ($ar && ($ar->auditee || $ar->notes))
        <p class="mut pre">@if ($ar->auditee)من قابلهم: {{ $ar->auditee }}@if ($ar->audited_on) · {{ $ar->audited_on->format('Y-m-d') }}@endif
@endif{{ $ar->notes }}</p>
      @endif
      @if ($list->isNotEmpty())
        <table class="t2">
          <tr><th class="c" style="width:4%">م</th><th style="width:9%">الدرجة</th><th>الملاحظة والدليل</th><th style="width:14%">المرجع</th><th style="width:27%">الرد والإجراء</th></tr>
          @foreach ($list as $f)
            <tr>
              <td class="c">{{ $f->no }}</td>
              <td><span class="gr" style="background:{{ $G[$f->grade][1] }}">{{ $f->gradeLabel() }}</span></td>
              <td>{{ $f->text }}@if ($f->evidence)<div class="mut" style="font-size:11px">الدليل: {{ $f->evidence }}</div>@endif</td>
              <td>{{ $f->ref }}</td>
              <td>@if ($f->needsAction()){{ $f->action ?: '……………' }}@if ($f->owner || $f->due_date)<div class="mut" style="font-size:11px">{{ $f->owner?->displayName() }} {{ $f->due_date?->format('Y-m-d') }} {{ $f->dev_no }}</div>@endif @else — @endif</td>
            </tr>
          @endforeach
        </table>
      @else
        <p class="mut">لا توجد ملاحظات.</p>
      @endif
    @endforeach

    <h3><i>٤.</i>التوقيعات</h3>
    <table class="t2 sg">
      <tr><th style="width:16%"></th><th>المراجع</th><th>مدير إدارة الجودة</th><th>المدير الصيدلي</th></tr>
      <tr><td class="k">الاسم</td><td>{{ $a->lead ?: $a->auditor?->displayName() }}</td><td></td><td></td></tr>
      <tr><td class="k">التوقيع</td><td></td><td></td><td></td></tr>
      <tr><td class="k">التاريخ</td><td></td><td></td><td></td></tr>
    </table>

    <div class="foot">
      {{ $tenant->name_ar }} · تقرير مراجعة {{ $a->typeLabel() }} ({{ $a->number }}). تُتابع الإجراءات التصحيحية في سجل CAPA (SH-008) وتُعرض النتائج في مراجعة الإدارة (SH-013).
      <br>طُبع من نظام إتقان بتاريخ <span class="ltr">{{ $printedAt }}</span> بواسطة {{ $printedBy }}.
    </div>
  </div>
@endsection