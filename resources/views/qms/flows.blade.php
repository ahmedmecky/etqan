@extends('layouts.app')

@section('title', $flow ? 'مخطط سير العمل · ' . $flow['code'] : 'مخططات سير العمل')

@push('head')
<style>
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:12px}
  .tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px;align-items:center}
  .tabs a{padding:8px 16px;border-radius:99px;border:1px solid var(--line);background:#fff;text-decoration:none;font-weight:700;font-size:14px;color:var(--ink)}
  .tabs a span{font-weight:400;font-size:12px;color:var(--muted);direction:ltr;display:inline-block;margin-inline-start:4px}
  .tabs a.on{background:var(--brand);border-color:var(--brand);color:#fff}
  .tabs a.on span{color:#d7ebe8}
  .tabs select{width:auto;min-width:260px;padding:8px 12px;border-radius:99px}
  .fw{background:#fff;border:1px solid var(--line);border-radius:12px;overflow:hidden;position:relative}
  .fw iframe{border:0;width:1440px;transform-origin:top right;position:absolute;top:0;right:0;background:#f7f9f8}
  .note{font-size:12.5px;color:var(--muted);margin-bottom:10px}

  /* ===== المخطط التلقائي ===== */
  .fc{background:#fff;border:1px solid var(--line);border-radius:12px;padding:18px 20px}
  .fc .ph{text-align:center;margin-bottom:8px}
  .fc .ph b{display:block;font-size:19px;color:#0f5e5a}
  .fc .ph small{font-size:12px;color:var(--muted);direction:ltr;display:inline-block}
  .fc .lay{display:grid;grid-template-columns:minmax(0,1.9fr) minmax(0,1fr);gap:18px;align-items:start}
  @media (max-width:900px){ .fc .lay{grid-template-columns:1fr} }
  .fc .legend{display:flex;flex-wrap:wrap;gap:6px;justify-content:center;margin:6px 0 14px}
  .fc .who{display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:700;border-radius:99px;padding:1px 9px;color:#fff;white-space:nowrap}
  .fc .term{width:max-content;max-width:90%;margin:0 auto;background:#0f5e5a;color:#fff;border-radius:99px;padding:8px 22px;font-weight:700;font-size:14px;text-align:center}
  .fc .term.end{background:#1c2826}
  .fc .arr{width:2px;height:18px;background:#9fb3b0;margin:0 auto;position:relative}
  .fc .arr::after{content:'';position:absolute;bottom:-6px;left:50%;transform:translateX(-50%);border:6px solid transparent;border-top-color:#9fb3b0;border-bottom:0}
  .fc .stage{border:2px solid #cfe5e1;border-radius:14px;padding:10px 12px 12px;background:#f7fbfa;break-inside:avoid-page}
  .fc .stage > h3{margin:0 0 8px;font-size:15px;color:#0f5e5a;display:flex;align-items:center;gap:8px}
  .fc .stage > h3 i{font-style:normal;background:#c08a1e;color:#fff;border-radius:8px;padding:0 8px;font-size:13px}
  .fc .box{background:#fff;border:1.5px solid #9fb3b0;border-radius:10px;padding:8px 12px;font-size:13.5px;line-height:1.75;break-inside:avoid;position:relative}
  .fc .box .n{position:absolute;top:-9px;right:-9px;width:24px;height:24px;border-radius:50%;background:#0f5e5a;color:#fff;font-size:11.5px;font-weight:700;display:flex;align-items:center;justify-content:center}
  .fc .box .m{display:flex;flex-wrap:wrap;gap:4px;margin-top:4px;align-items:center}
  .fc .box .rec{font-size:11.5px;color:#9a6300;font-weight:700;direction:ltr}
  .fc .box + .arr{height:12px}
  .fc .side h3{font-size:15px;color:#c0362c;margin:0 0 8px}
  .fc .pb{border:1.5px solid #f0c4bf;border-radius:10px;padding:8px 10px;margin-bottom:10px;background:#fffafa;break-inside:avoid}
  .fc .pb .q{display:flex;gap:8px;align-items:flex-start;font-weight:700;font-size:13.5px;line-height:1.6}
  .fc .pb .q::before{content:'؟';flex:none;width:22px;height:22px;background:#c0362c;color:#fff;transform:rotate(45deg);border-radius:4px;display:flex;align-items:center;justify-content:center;margin:2px 4px 0 2px;font-size:12px}
  .fc .pb .a{font-size:13px;line-height:1.7;margin:4px 30px 0 0;color:#33403d}
  .fc .pb .a b{color:#0f5e5a}
  .fc .recs{margin-top:14px;border-top:1px dashed #9fb3b0;padding-top:8px;font-size:13px;line-height:1.9}
  .fc .recs b{color:#0f5e5a}
  .fc .ft{margin-top:12px;font-size:11px;color:var(--muted);text-align:center}
  @page{size:A4;margin:10mm}
  @media print{
    header.top,footer.foot,.head-actions,.tabs,.note,main.wrap>.alert{display:none !important}
    main.wrap{padding:0 !important;max-width:none !important}
    body{background:#fff !important}
    .fc{border:0;padding:0}
    .fc .lay{grid-template-columns:1fr}
    *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  }
</style>
@endpush

@section('content')
  <div class="head-actions">
    <div>
      <h1 class="page-title">مخططات سير العمل</h1>
      <p class="page-sub" style="margin:0">خطوات كل إجراء والمسؤول عنها والسجلات وما يُفعل عند حدوث مشكلة · مرجع للتدريب والتفتيش</p>
    </div>
    <div class="actions">
      <a href="{{ route('qms.map') }}" class="btn ghost">خريطة الجودة</a>
      @if ($flow)
        <a href="{{ route('workcards.sop', $flow['code']) }}" target="_blank" class="btn ghost">البطاقات المبسطة</a>
        <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
      @else
        <a href="{{ asset($flows[$key][2]) }}" target="_blank" class="btn gold">فتح للطباعة</a>
      @endif
    </div>
  </div>

  <div class="tabs">
    @foreach ($flows as $k => $x)
      <a href="{{ route('qms.flows', ['f' => $k]) }}" class="{{ $k === $key ? 'on' : '' }}">{{ $x[0] }}<span>{{ $x[1] }}</span></a>
    @endforeach
    <select onchange="if (this.value) location.href = '{{ route('qms.flows') }}?f=' + this.value + (['SH-001', 'SH-004'].includes(this.value) ? '&auto=1' : '')" aria-label="مخطط إجراء">
      <option value="">مخطط أي إجراء (٣٤)…</option>
      @foreach ($sops as $s)<option value="{{ $s['code'] }}" @selected($flow && $flow['code'] === $s['code'])>{{ $s['code'] }} · {{ $s['title'] }}</option>@endforeach
    </select>
  </div>

  @if (! $flow)
    @php
      $f = $flows[$key];
    @endphp
    <div class="note">على الموبايل: اسحب للأسفل لقراءة المخطط، واضغط «فتح للطباعة» للتكبير. المخطط المبسط التلقائي: <a href="{{ route('qms.flows', ['f' => $f[1], 'auto' => 1]) }}">من خطوات {{ $f[1] }}</a>.</div>
    <div class="fw" id="fw" data-h="{{ $f[3] }}">
      <iframe src="{{ asset($f[2]) }}" title="{{ $f[0] }}" height="{{ $f[3] }}" scrolling="no" loading="lazy"></iframe>
    </div>
  @else
    @if (! empty($hand))
      <div class="note">يوجد مخطط تفصيلي مرسوم لهذا الإجراء: <a href="{{ route('qms.flows', ['f' => $hand]) }}">{{ $flows[$hand][0] }}</a>.</div>
    @endif
    <div class="fc">
      <div class="ph"><b>{{ $flow['title'] }}</b><small>{{ $flow['code'] }} · الإصدار {{ $flow['version'] }} · {{ $flow['steps'] }} خطوة</small></div>
      <div class="legend">
        @foreach ($flow['owners'] as $w => $c)<span class="who" style="background:{{ $c }}">{{ $w }}</span>@endforeach
      </div>

      <div class="lay">
        <div class="main">
          <div class="term">بداية: {{ $flow['title'] }}</div>
          <div class="arr"></div>
          @foreach ($flow['phases'] as $pi => $ph)
            <div class="stage">
              <h3><i>{{ $pi + 1 }}</i>{{ preg_replace('/^[\p{L}]{1,2}\.\s*/u', '', $ph['title']) }}</h3>
              @foreach ($ph['rows'] as $ri => $st)
                <div class="box">
                  <span class="n">{{ $st['n'] }}</span>
                  {{ $st['text'] }}
                  <div class="m">
                    @foreach ($st['who'] as $w)<span class="who" style="background:{{ $flow['owners'][$w] ?? '#334155' }}">{{ $w }}</span>@endforeach
                    @if ($st['rec'])<span class="rec">📝 {{ $st['rec'] }}</span>@endif
                  </div>
                </div>
                @unless ($loop->last)<div class="arr"></div>@endunless
              @endforeach
            </div>
            <div class="arr"></div>
          @endforeach
          <div class="term end">نهاية: حفظ السجلات</div>

          @if ($flow['records'])
            <div class="recs"><b>السجلات والنماذج:</b>
              @foreach ($flow['records'] as [$c, $t])<span class="ltr">{{ $c }}</span> {{ $t }}@unless ($loop->last) · @endunless @endforeach
            </div>
          @endif
        </div>

        <div class="side">
          <h3>⚠ عند حدوث مشكلة</h3>
          @forelse ($flow['problems'] as $p)
            <div class="pb">
              <div class="q">{{ $p['if'] }}</div>
              <div class="a">{{ $p['do'] }}@if ($p['who'])<br><b>المسؤول:</b> {{ $p['who'] }}@endif</div>
            </div>
          @empty
            <p class="note">لا توجد حالات مسجلة في الإجراء.</p>
          @endforelse
        </div>
      </div>
      <div class="ft">مخطط تلقائي من خطوات الإجراء المعتمد {{ $flow['code'] }} في مكتبة إتقان · الإجراء الكامل هو المرجع</div>
    </div>
  @endif
@endsection

@push('scripts')
<script>
  (function () {
    const box = document.getElementById('fw');
    if (!box) return;
    const fr = box.querySelector('iframe'), h = +box.dataset.h || 2600;
    function fit() { const s = Math.min(1, box.clientWidth / 1440); fr.style.transform = `scale(${s})`; box.style.height = Math.ceil(h * s) + 'px'; }
    fit(); window.addEventListener('resize', fit);
  })();
</script>
@endpush