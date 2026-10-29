@extends('layouts.app')

@section('title', 'طباعة مجمعة · ' . count($items) . ' وثيقة')

@push('head')
  @include('documents._print-style')
  @include('jobs._jd-style')
  <style>
    .bi{break-after:page;page-break-after:always;margin-bottom:22px}
    .bi:last-of-type{break-after:auto;page-break-after:auto}
    .bt{max-width:960px;margin:0 auto 12px;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;font-size:13.5px}
    .bt .lst{font-size:12.5px;color:var(--muted);line-height:1.8}
    .bt .sk{background:#fdf1dc;color:#7a4f00;border-radius:8px;padding:6px 10px;font-size:12.5px;line-height:1.8}
    @media print{ .bt{display:none !important} .bi > .pd,.bi > .jd{margin:0} .bi .pd .wm{position:absolute !important} }
  </style>
@endpush

@section('content')
  <div class="bt">
    <div>
      <a href="{{ route('documents.index') }}">← سجل الوثائق</a> · <b>{{ count($items) }} وثيقة</b> في ملف واحد · كل وثيقة تبدأ في صفحة جديدة
      @if (! $withForms)
        · <a href="{{ route('documents.print.batch', ['ids' => $ids, 'forms' => 1]) }}">مع نماذج الإجراءات</a>
      @else
        · <a href="{{ route('documents.print.batch', ['ids' => $ids]) }}">بدون النماذج</a>
      @endif
      <div class="lst">{{ collect($items)->pluck('code')->implode(' · ') }}</div>
      @if ($skipped)
        <div class="sk">لم تُضف {{ count($skipped) }}: @foreach ($skipped as [$c, $why])<span class="ltr">{{ $c }}</span> ({{ $why }})@unless ($loop->last) · @endunless @endforeach</div>
      @endif
    </div>
    <button type="button" class="btn gold" onclick="window.print()">طباعة الكل</button>
  </div>

  @forelse ($items as $it)
    <div class="bi">
      @if ($it['type'] === 'sop')
        @include('documents._sop-body', $it['vars'])
      @elseif ($it['type'] === 'kpi')
        @include('documents._kpi-body', $it['vars'])
      @else
        @include('jobs._jd-body', $it['vars'])
      @endif
    </div>
  @empty
    <div class="card" style="max-width:960px;margin:0 auto;text-align:center;color:var(--muted)">لا توجد وثائق قابلة للطباعة في الاختيار.</div>
  @endforelse
@endsection