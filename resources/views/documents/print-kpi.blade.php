@extends('layouts.app')

@section('title', $doc->code . ' · ' . $doc->title)

@push('head')
  @include('documents._print-style')
@endpush

@section('content')
  @php
    $eff = $v?->effective_date?->format('Y-m-d');
    $issued = $v?->submitted_at?->timezone('Africa/Cairo')->format('Y-m-d');
    $review = $v?->review_due?->format('Y-m-d');
    $verNo = $v?->version ?? '01';
    $logo = $tenant->logoUrl();
    $variants = $set['variants'] ?? [];
    $pct = fn ($x) => rtrim(rtrim(number_format((float) $x * 100, 1), '0'), '.') . '٪';
    $range = function ($k) use ($pct) {
        $pre = ($k['type'] ?? '') === 'rating' ? 'تقييم من ١ إلى ٥ · ' : (($k['type'] ?? '') === 'lower' ? 'الأقل أفضل · ' : '');

        return $pre . ((float) $k['min'] > 0 ? 'الإنجاز أقل من ' . $pct($k['min']) . ' = صفر · ' : '') . 'السقف ' . $pct($k['max']);
    };
  @endphp

  <div class="ptools">
    <div class="fl">
      <a href="{{ route('documents.show', $doc) }}" style="border:0;padding:0;font-size:13.5px">← {{ $doc->code }}</a>
      @if ($draft)<span class="warn">{{ $v ? 'نسخة غير سارية: ' . $v->stageLabel() : 'لا يوجد إصدار بعد: نص المكتبة كما هو' }}</span>@endif
    </div>
    <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
  </div>

  @include('documents._kpi-body')
@endsection