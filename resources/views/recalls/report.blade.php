@extends('layouts.app')

@section('title', 'متابعة وتسوية السحب · ' . $r->number)

@push('head')
  @include('documents._print-style')
  <style>.pd table.t2 td,.pd table.t2 th{font-size:11.5px}</style>
@endpush

@section('content')
  @php
    $logo = $tenant->logoUrl();
  @endphp

  <div class="ptools">
    <div class="fl"><a href="{{ route('recalls.show', $r) }}" style="border:0;padding:0;font-size:13.5px">← {{ $r->number }}</a></div>
    <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
  </div>

  <div class="pd">
    <div class="hd">
      <div class="tn">
        @if ($logo)<img src="{{ $logo }}" alt="">@endif
        <div><b>{{ $tenant->name_ar }}</b><small>{{ $tenant->name_en }}</small><small>نظام إدارة الجودة · إتقان</small></div>
      </div>
      <div class="tt"><span>الاستدعاء والسحب من السوق</span><b>سجل متابعة العملاء وتسوية السحب</b><small>SH-010-F02 · F03</small></div>
      <div class="mt">رقم السحب: <b>{{ $r->number }}</b><br>الحالة: <b>{{ $r->isOpen() ? 'مفتوح' : 'مغلق' }}</b></div>
    </div>

    <table class="dates">
      <tr><td class="k">الصنف</td><td><b>{{ $r->product }}</b></td><td class="k">التشغيلة</td><td class="ltr" style="text-align:right">{{ $r->batch }}</td></tr>
      <tr><td class="k">رقم المنشور</td><td>{{ $r->circular_no ?: '—' }} · {{ $r->sourceLabel() }}</td><td class="k">درجة الاستدعاء</td><td>{{ $r->classLabel() }}</td></tr>
      <tr><td class="k">تاريخ الاستلام</td><td class="ltr" style="text-align:right">{{ $r->received_on->format('Y-m-d') }}</td><td class="k">آخر موعد للإبلاغ</td><td class="ltr" style="text-align:right">{{ $r->deadline?->format('Y-m-d') ?? '—' }}</td></tr>
    </table>

    <h3><i>١.</i>متابعة الاتصال بالعملاء والاسترجاع (F02)</h3>
    <table class="t2">
      <tr><th class="c" style="width:4%">م</th><th>العميل</th><th>الكود</th><th>التليفون</th><th class="c">المبيع</th><th>وقت الإبلاغ</th><th>الوسيلة</th><th>الإقرار</th><th class="c">المسترجع</th><th>تاريخ الاسترجاع</th><th>صورة</th></tr>
      @forelse ($r->customers as $i => $c)
        <tr>
          <td class="c">{{ $i + 1 }}</td><td>{{ $c->name }}</td><td class="ltr">{{ $c->code }}</td><td class="ltr">{{ $c->phone }}</td>
          <td class="c">{{ $c->qty_sold }}</td><td class="ltr">{{ $c->notified_at?->timezone('Africa/Cairo')->format('Y-m-d H:i') }}</td>
          <td>{{ $c->methodLabel() }}</td><td>{{ $c->declarationLabel() }}</td><td class="c">{{ $c->qty_returned }}</td>
          <td class="ltr">{{ $c->returned_on?->format('Y-m-d') }}</td><td class="c">{{ $c->file_path ? '✓' : '' }}</td>
        </tr>
      @empty
        <tr><td colspan="11" class="c">لا يوجد عملاء.</td></tr>
      @endforelse
      <tr class="g"><td colspan="4">الإجمالي ({{ $s['customers'] }} عميل · {{ $s['notified'] }} تم إبلاغه · {{ $s['declared'] }} إقرار)</td><td class="c">{{ $s['sold'] }}</td><td colspan="3"></td><td class="c">{{ $s['returned'] }}</td><td colspan="2"></td></tr>
    </table>

    <h3><i>٢.</i>تسوية السحب (F03)</h3>
    <table class="t2">
      <tr><td class="k">(أ) الكمية المستلمة من التشغيلة</td><td>{{ $s['received'] ?? '……………' }}</td><td class="k">(ب) الكمية المبيعة للعملاء</td><td>{{ $s['sold'] }} · عدد العملاء {{ $s['customers'] }}</td></tr>
      <tr><td class="k">(ج) الموجود بالمخزن والمنقول للمسحوبات</td><td>{{ $s['inStock'] ?? '……………' }}</td><td class="k">(د) المسترجع من العملاء</td><td>{{ $s['returned'] }}</td></tr>
      <tr><td class="k">(هـ) غير المسترد = أ − ج − د</td><td>{{ $s['unrecovered'] ?? '……………' }}</td><td class="k">نسبة نجاح السحب = د ÷ ب</td><td>{{ $s['success'] !== null ? $s['success'] . '٪' : '……………' }}</td></tr>
      <tr><td class="k">العملاء الذين أُبلغوا في المدة</td><td>{{ $s['notifiedPct'] !== null ? $s['notifiedPct'] . '٪' : '—' }} ({{ $s['inTime'] }} من {{ $s['customers'] }})</td><td class="k">التصرف في المسحوب</td><td>{{ \App\Support\Recalls::DISPOSALS[$r->disposal] ?? '……………' }} {{ $r->disposal_ref }}</td></tr>
      <tr><td class="k">إبلاغ هيئة الدواء</td><td class="ltr" style="text-align:right">{{ $r->eda_notified_on?->format('Y-m-d') ?? '……………' }}</td><td class="k">إبلاغ المصنع / المورد</td><td class="ltr" style="text-align:right">{{ $r->supplier_notified_on?->format('Y-m-d') ?? '……………' }}</td></tr>
      <tr><td class="k">إجراء تصحيحي SH-008</td><td>{{ $r->dev_no ?: 'لا' }}</td><td class="k">ملاحظات</td><td>{{ $r->notes }}</td></tr>
    </table>

    <h3><i>٣.</i>الاعتماد</h3>
    <table class="t2 sg">
      <tr><th style="width:16%"></th><th>المدير الصيدلي</th><th>مدير إدارة الجودة</th></tr>
      <tr><td class="k">الاسم</td><td>{{ $r->closer?->displayName() }}</td><td></td></tr>
      <tr><td class="k">التوقيع</td><td></td><td></td></tr>
      <tr><td class="k">التاريخ</td><td class="ltr" style="text-align:right">{{ $r->closed_at?->timezone('Africa/Cairo')->format('Y-m-d') }}</td><td></td></tr>
    </table>

    <div class="foot">
      {{ $tenant->name_ar }} · الاستدعاء والسحب من السوق (SH-010) · {{ $r->number }}. يُرفق بهذا التقرير إقرارات الصيدليات الموقعة والمختومة.
      <br>طُبع من نظام إتقان بتاريخ <span class="ltr">{{ $printedAt }}</span> بواسطة {{ $printedBy }}.
    </div>
  </div>
@endsection