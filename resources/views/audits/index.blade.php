@extends('layouts.app')

@section('title', 'المراجعات الخارجية')

@push('head')
  @include('audits._style')
@endpush

@section('content')
<div class="au">
  <div class="head-actions">
    <div>
      <h1 class="page-title">المراجعات الخارجية</h1>
      <p class="page-sub" style="margin:0">مراجعة استشاري أو جهة مستقلة · تفتيش هيئة الدواء · مراجعة عميل <span class="ltr">(SH-012 · SH-025)</span></p>
    </div>
  </div>

  @if ($manage)
    <details class="new" {{ $audits->isEmpty() || $errors->any() ? 'open' : '' }}>
      <summary>+ مراجعة جديدة (الرقم <span class="ltr">{{ $next }}</span>)</summary>
      <div class="body">
        <form method="POST" action="{{ route('audits.store') }}" enctype="multipart/form-data">
          @csrf
          @include('audits._plan', ['a' => null])
          <div class="actions" style="margin-top:10px"><button type="submit" class="btn">فتح المراجعة</button></div>
        </form>
        <p class="hint" style="margin:8px 0 0">الخطوات: الخطة والإدارات ← حساب مؤقت للمراجع (اطلاع فقط + كتابة ملاحظاته) ← شاشة لكل إدارة ← التقرير ← رد الإدارة والإجراءات ← الإغلاق.
          الجهة الاستشارية تُؤهل أولاً طبقاً لإجراء SH-025 مع اتفاقية سرية.</p>
      </div>
    </details>
  @endif

  @forelse ($audits as $a)
    @php
      $s = $stats[$a->id];
    @endphp
    <a href="{{ route('audits.show', $a) }}" class="rl {{ $a->status === 'closed' ? 'closed' : '' }}">
      <div><b class="ltr" style="text-align:right">{{ $a->number }}</b><small>{{ $a->start_date->format('Y-m-d') }}</small></div>
      <div><b>{{ $a->title }}</b><small>{{ $a->typeLabel() }}@if ($a->company) · {{ $a->company }}@endif</small></div>
      <div><small>الإدارات</small><b>{{ $s['areasDone'] }} / {{ $s['areas'] }}</b></div>
      <div><small>الملاحظات</small><b>{{ $s['total'] }}</b>@if ($s['action'])<small>{{ $s['open'] }} مفتوحة من {{ $s['action'] }}{{ $s['grades']['critical'] ? ' · ' . $s['grades']['critical'] . ' حرجة' : '' }}</small>@endif</div>
      <div><span class="st {{ $a->status === 'closed' ? 'ok' : 'warn' }}">{{ $a->statusLabel() }}</span></div>
    </a>
  @empty
    <div class="empty">لا توجد مراجعات خارجية مسجلة.</div>
  @endforelse
</div>
@endsection