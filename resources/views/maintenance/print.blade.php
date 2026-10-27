@extends('layouts.app')

@section('title', 'سجل المعدات والأجهزة وعقود الصيانة')

@push('head')
  @include('documents._print-style')
  <style>
    .pd table.t2 td,.pd table.t2 th{font-size:11.5px}
    .pd .x{color:#b3261e;font-weight:700}
    .pd .w{color:#8a5a00;font-weight:700}
  </style>
@endpush

@section('content')
  @php
    $logo = $tenant->logoUrl();
    $M = \App\Support\Maintenance::class;
    $L = \App\Support\Maintenance::STATE_LABELS;
  @endphp

  <div class="ptools">
    <div class="fl"><a href="{{ route('maint.index') }}" style="border:0;padding:0;font-size:13.5px">← الصيانة والأجهزة</a></div>
    <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
  </div>

  <div class="pd">
    <div class="hd">
      <div class="tn">
        @if ($logo)<img src="{{ $logo }}" alt="">@endif
        <div><b>{{ $tenant->name_ar }}</b><small>{{ $tenant->name_en }}</small><small>نظام إدارة الجودة · إتقان</small></div>
      </div>
      <div class="tt"><span>التأهيل والتحقق والمعايرة · مكافحة الحريق</span><b>سجل المعدات والأجهزة وعقود الصيانة</b><small>SH-023-F01 · SH-017</small></div>
      <div class="mt">
        تاريخ الطباعة:<br><b class="ltr">{{ $printedAt }}</b><br>
        عدد الأجهزة: <b>{{ $assets->count() }}</b>
      </div>
    </div>

    <h3><i>١.</i>عقود الصيانة السارية</h3>
    <table class="t2">
      <tr><th style="width:4%">م</th><th>الشركة</th><th>نوع العقد</th><th>رقم العقد</th><th>التليفون</th><th>البداية</th><th>الانتهاء</th><th>الحالة</th><th class="c">الأجهزة</th></tr>
      @forelse ($contracts as $i => $c)
        @php
          $st = $c->state();
        @endphp
        <tr>
          <td class="c">{{ $i + 1 }}</td><td><b>{{ $c->company }}</b></td><td>{{ $c->kindLabel() }}</td><td class="ltr">{{ $c->contract_no }}</td>
          <td class="ltr">{{ $c->phone }}</td><td class="ltr">{{ $c->start_date?->format('Y-m-d') }}</td><td class="ltr">{{ $c->end_date->format('Y-m-d') }}</td>
          <td class="{{ $st === 'expired' ? 'x' : (in_array($st, ['soon', 'week'], true) ? 'w' : '') }}">{{ $L[$st] }} · {{ $M::leftText($c->daysLeft()) }}</td>
          <td class="c">{{ $c->assets_count }}</td>
        </tr>
      @empty
        <tr><td colspan="9" class="c">لا توجد عقود مسجلة.</td></tr>
      @endforelse
    </table>

    <h3><i>٢.</i>سجل الأجهزة</h3>
    <table class="t2">
      <tr><th style="width:4%">م</th><th>الجهاز</th><th>الفئة</th><th>الرقم التسلسلي</th><th>الكود</th><th>الموقع</th><th>عقد الصيانة</th><th>آخر صيانة / معايرة</th><th>التاريخ القادم</th><th>الحالة</th><th>آخر جرد</th></tr>
      @forelse ($assets as $i => $a)
        @php
          $st = $a->state();
        @endphp
        <tr>
          <td class="c">{{ $i + 1 }}</td><td><b>{{ $a->name }}</b>@if ($a->brand)<br><span class="mut">{{ $a->brand }} {{ $a->model }}</span>@endif</td>
          <td>{{ $a->categoryLabel() }}</td><td class="ltr">{{ $a->serial }}</td><td class="ltr">{{ $a->tag_no }}</td><td>{{ $a->location }}</td>
          <td>{{ $a->contract?->company ?? '—' }}</td><td class="ltr">{{ $a->last_service?->format('Y-m-d') }}</td>
          <td class="{{ $st === 'expired' ? 'x' : (in_array($st, ['soon', 'week'], true) ? 'w' : '') }}">@if ($a->due_date)<span class="ltr">{{ $a->due_date->format('Y-m-d') }}</span><br><span class="mut">{{ $a->dueLabel() }}</span>@endif</td>
          <td>{{ $a->statusLabel() }}</td>
          <td class="ltr">{{ $a->last_counted?->format('Y-m-d') }}@if ($a->count_result === 'missing') <span class="x">غير موجود</span>@endif</td>
        </tr>
      @empty
        <tr><td colspan="11" class="c">لا توجد أجهزة مسجلة.</td></tr>
      @endforelse
    </table>

    <h3><i>٣.</i>المراجعة</h3>
    <table class="t2 sg">
      <tr><th style="width:16%"></th><th>مسؤول الأجهزة / السلامة</th><th>المدير الصيدلي</th><th>مدير إدارة الجودة</th></tr>
      <tr><td class="k">الاسم</td><td></td><td></td><td></td></tr>
      <tr><td class="k">التوقيع</td><td></td><td></td><td></td></tr>
      <tr><td class="k">التاريخ</td><td></td><td></td><td></td></tr>
    </table>

    <div class="foot">
      {{ $tenant->name_ar }} · سجل المعدات والأجهزة (SH-023-F01). النسخة المطبوعة غير خاضعة للتحكم ما لم تحمل ختم إدارة الجودة.
      <br>طُبعت من نظام إتقان بتاريخ <span class="ltr">{{ $printedAt }}</span> بواسطة {{ $printedBy }}.
    </div>
  </div>
@endsection