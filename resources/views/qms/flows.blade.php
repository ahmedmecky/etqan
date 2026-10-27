@extends('layouts.app')

@section('title', 'مخططات سير العمل')

@push('head')
<style>
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:12px}
  .tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}
  .tabs a{padding:8px 16px;border-radius:99px;border:1px solid var(--line);background:#fff;text-decoration:none;font-weight:700;font-size:14px;color:var(--ink)}
  .tabs a span{font-weight:400;font-size:12px;color:var(--muted);direction:ltr;display:inline-block;margin-inline-start:4px}
  .tabs a.on{background:var(--brand);border-color:var(--brand);color:#fff}
  .tabs a.on span{color:#d7ebe8}
  .fw{background:#fff;border:1px solid var(--line);border-radius:12px;overflow:hidden;position:relative}
  .fw iframe{border:0;width:1440px;transform-origin:top right;position:absolute;top:0;right:0;background:#f7f9f8}
  .note{font-size:12.5px;color:var(--muted);margin-bottom:10px}
</style>
@endpush

@section('content')
  @php
    $f = $flows[$key];
  @endphp
  <div class="head-actions">
    <div>
      <h1 class="page-title">مخططات سير العمل</h1>
      <p class="page-sub" style="margin:0">خطوات كل عملية والمسؤول عنها والقرارات · مرجع للتدريب والتفتيش</p>
    </div>
    <div class="actions">
      <a href="{{ route('qms.map') }}" class="btn ghost">خريطة الجودة</a>
      <a href="{{ asset($f[2]) }}" target="_blank" class="btn gold">فتح للطباعة</a>
    </div>
  </div>

  <div class="tabs">
    @foreach ($flows as $k => $x)
      <a href="{{ route('qms.flows', ['f' => $k]) }}" class="{{ $k === $key ? 'on' : '' }}">{{ $x[0] }}<span>{{ $x[1] }}</span></a>
    @endforeach
  </div>
  <div class="note">على الموبايل: اسحب للأسفل لقراءة المخطط، واضغط «فتح للطباعة» للتكبير.</div>

  <div class="fw" id="fw" data-h="{{ $f[3] }}">
    <iframe src="{{ asset($f[2]) }}" title="{{ $f[0] }}" height="{{ $f[3] }}" scrolling="no" loading="lazy"></iframe>
  </div>
@endsection

@push('scripts')
<script>
  (function () {
    const box = document.getElementById('fw'), fr = box.querySelector('iframe'), h = +box.dataset.h || 2600;
    function fit() { const s = Math.min(1, box.clientWidth / 1440); fr.style.transform = `scale(${s})`; box.style.height = Math.ceil(h * s) + 'px'; }
    fit(); window.addEventListener('resize', fit);
  })();
</script>
@endpush