@extends('layouts.app')

@section('title', $doc->code . ' · ' . $sop['title'])

@push('head')
  @include('documents._print-style')
@endpush

@section('content')
  @php
    $eff = $v?->effective_date?->format('Y-m-d');
    $issued = $v?->submitted_at?->timezone('Africa/Cairo')->format('Y-m-d');
    $review = $v?->review_due?->format('Y-m-d');
    $verNo = $v?->version ?? $sop['version'];
    $n = 0;
    $history = $doc->versions->filter(fn ($x) => $x->effective_date || ($v && $x->id === $v->id))->sortBy('id')->values();
    $logo = $tenant->logoUrl();
  @endphp

  <div class="ptools">
    <div class="fl">
      <a href="{{ route('documents.show', $doc) }}" style="border:0;padding:0;font-size:13.5px">← {{ $doc->code }}</a>
      @if ($draft)<span class="warn">{{ $v ? 'نسخة غير سارية: ' . $v->stageLabel() : 'لا يوجد إصدار بعد: نص المكتبة كما هو' }}</span>@endif
      @if (count($sop['forms']))
        <span style="color:var(--muted)">طباعة نموذج فارغ:</span>
        @if ($only)<a href="{{ route('documents.print', [$doc] + ($v ? ['v' => $v->id] : [])) }}">الإجراء كاملاً</a>@endif
        @foreach ($sop['forms'] as $f)
          <a href="{{ route('documents.print', [$doc, 'form' => $f['code']] + ($v ? ['v' => $v->id] : [])) }}" title="{{ $f['title'] }}" class="ltr">{{ $f['code'] }}</a>
        @endforeach
      @endif
    </div>
    <div class="actions" style="gap:6px">
      <a href="{{ route('workcards.sop', $doc->code) }}" class="btn ghost small" target="_blank">البطاقات المبسطة</a>
      <a href="{{ route('qms.flows', ['f' => $doc->code]) }}" class="btn ghost small" target="_blank">مخطط سير العمل</a>
      <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
    </div>
  </div>

  @include('documents._sop-body')
@endsection