@extends('layouts.app')

@section('title', $title)

@push('head')
  @include('documents._print-style')
@endpush

@section('content')
  @php
    $logo = $tenant->logoUrl();
    $stLabel = ['effective' => 'ساري', 'pending' => 'قيد الإعداد أو المراجعة', 'none' => 'بدون إصدار', 'review' => 'تجاوز موعد المراجعة', 'obsolete' => 'ملغاة'];
  @endphp

  <div class="ptools">
    <div class="fl"><a href="{{ route('documents.index') }}" style="border:0;padding:0;font-size:13.5px">← سجل الوثائق</a> <span>{{ $rows->count() }} وثيقة</span></div>
    <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
  </div>

  <div class="pd">
    <div class="hd">
      <div class="tn">
        @if ($logo)<img src="{{ $logo }}" alt="">@endif
        <div><b>{{ $tenant->name_ar }}</b><small>{{ $tenant->name_en }}</small><small>نظام إدارة الجودة · إتقان</small></div>
      </div>
      <div class="tt"><span>التحكم في الوثائق</span><b>{{ $title }}</b><small>SH-014</small></div>
      <div class="mt">عدد الوثائق: <b>{{ $rows->count() }}</b><br>بتاريخ: <b>{{ substr($printedAt, 0, 10) }}</b></div>
    </div>

    <table class="t2">
      <tr><th class="c" style="width:5%">م</th><th style="width:12%">الكود</th><th>العنوان</th><th style="width:13%">النوع</th><th class="c" style="width:8%">الإصدار</th><th style="width:12%">تاريخ السريان</th><th style="width:12%">المراجعة القادمة</th><th style="width:13%">الحالة</th></tr>
      @foreach ($rows as $i => $r)
        @php
          $d = $r['doc'];
        @endphp
        <tr>
          <td class="c">{{ $i + 1 }}</td>
          <td class="ltr" style="text-align:right"><b>{{ $d->code }}</b></td>
          <td>{{ $d->title }}@if ($d->dept)<div class="mut" style="font-size:11px">{{ $d->dept }}</div>@endif</td>
          <td>{{ $d->typeLabel() }}</td>
          <td class="c ltr">{{ $r['current']?->version ?? '—' }}</td>
          <td class="ltr" style="text-align:right">{{ $r['current']?->effective_date?->format('Y-m-d') ?? '—' }}</td>
          <td class="ltr" style="text-align:right">{{ $r['current']?->review_due?->format('Y-m-d') ?? '—' }}</td>
          <td>{{ $stLabel[$r['state']] }}@if ($r['working']) <span class="mut" style="font-size:11px">(إصدار {{ $r['working']->version }} جارٍ)</span>@endif</td>
        </tr>
      @endforeach
    </table>

    <table class="t2 sg" style="margin-top:14px">
      <tr><th style="width:16%"></th><th>إعداد</th><th>مراجعة واعتماد</th></tr>
      <tr><td class="k">الوظيفة</td><td>مسؤول التحكم في الوثائق</td><td>مدير إدارة الجودة</td></tr>
      <tr><td class="k">الاسم</td><td></td><td></td></tr>
      <tr><td class="k">التوقيع</td><td></td><td></td></tr>
    </table>

    <div class="foot">
      {{ $tenant->name_ar }} · {{ $title }} من سجل الوثائق (SH-014).
      <br>طُبعت من نظام إتقان بتاريخ <span class="ltr">{{ $printedAt }}</span> بواسطة {{ $printedBy }}.
    </div>
  </div>
@endsection