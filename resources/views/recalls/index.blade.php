@extends('layouts.app')

@section('title', 'الاستدعاء والسحب')

@push('head')
  @include('recalls._style')
@endpush

@section('content')
<div class="rc">
  <div class="head-actions">
    <div>
      <h1 class="page-title">الاستدعاء والسحب من السوق</h1>
      <p class="page-sub" style="margin:0">سجل منشورات السحب · متابعة العملاء · إقرارات الصيدليات · التسوية <span class="ltr">(SH-010)</span></p>
    </div>
  </div>

  @if ($manage)
    <details class="new" {{ $recalls->isEmpty() || $errors->any() ? 'open' : '' }}>
      <summary>+ سحب جديد (سيأخذ الرقم <span class="ltr">{{ $next }}</span>)</summary>
      <div class="body">
        <form method="POST" action="{{ route('recalls.store') }}">
          @csrf
          @include('recalls._fields', ['r' => null])
          <div class="actions" style="margin-top:10px"><button type="submit" class="btn">فتح السحب وإبلاغ المبيعات</button></div>
        </form>
        <p class="hint" style="margin:8px 0 0">بعد الفتح: أضف العملاء الذين اشتروا التشغيلة (الكود والكمية) · اطبع إقرار لكل صيدلية · سجّل الإبلاغ والإقرار وصورته · ثم التسوية والإغلاق.</p>
      </div>
    </details>
  @endif

  @forelse ($recalls as $r)
    @php
      $s = $stats[$r->id];
    @endphp
    <a href="{{ route('recalls.show', $r) }}" class="rl {{ $r->isOpen() ? ($s['late'] ? 'late' : '') : 'closed' }}">
      <div><b class="ltr" style="text-align:right">{{ $r->number }}</b><small>{{ $r->received_on->format('Y-m-d') }}</small></div>
      <div><b>{{ $r->product }}</b><small>تشغيلة <span class="ltr">{{ $r->batch }}</span>@if ($r->circular_no) · منشور {{ $r->circular_no }}@endif</small></div>
      <div><span class="st {{ $r->class === '1' ? 'c1' : '' }}">{{ $r->classLabel() }}</span>@if ($r->deadline)<small>الإبلاغ قبل {{ $r->deadline->format('Y-m-d') }}</small>@endif</div>
      <div><small>الإقرارات</small><b>{{ $s['declared'] }} / {{ $s['customers'] }}</b>@if ($s['sold'])<small>استُرجع {{ $s['returned'] }} من {{ $s['sold'] }}</small>@endif</div>
      <div>@if ($r->isOpen())<span class="st {{ $s['late'] ? 'bad' : 'warn' }}">{{ $s['late'] ? 'تجاوز موعد الإبلاغ' : 'مفتوح' }}</span>@else<span class="st ok">مغلق</span>@endif</div>
    </a>
  @empty
    <div class="empty">لا توجد عمليات سحب مسجلة.</div>
  @endforelse
</div>
@endsection