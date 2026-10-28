@extends('layouts.app')

@section('title', count($list) === 1 ? 'بطاقة عمل · ' . $list[0][0]['title'] : 'بطاقات العمل · ' . ($sop['code'] ?? ''))

@push('head')
<style>
  .wc{max-width:820px;margin:0 auto 18px;background:#fff;border:1px solid var(--line);border-radius:12px;padding:24px 28px;font-size:16px;line-height:1.9;break-after:page;page-break-after:always}
  .wc:last-child{break-after:auto;page-break-after:auto}
  .wc .hd{display:flex;justify-content:space-between;align-items:center;gap:12px;border-bottom:3px solid #c08a1e;padding-bottom:8px;margin-bottom:12px}
  .wc .hd .tn{display:flex;align-items:center;gap:10px}
  .wc .hd img{max-height:52px;max-width:90px;object-fit:contain}
  .wc .hd b{display:block;font-size:14.5px;color:#0f5e5a;line-height:1.35}
  .wc .hd small{display:block;font-size:11px;color:#5f6f6c}
  .wc .hd .tt{text-align:left}
  .wc .hd .tt b{font-size:22px;color:#1c2826}
  .wc .hd .tt small{direction:ltr}
  .wc .me{display:grid;grid-template-columns:1fr 1fr;gap:6px 18px;background:#f3f9f8;border:1px solid #cfe5e1;border-radius:10px;padding:10px 14px;font-size:15px;margin-bottom:12px}
  .wc .me span{color:#5f6f6c;font-size:13px}
  .wc .me b{font-size:16px}
  .wc .me .ln{display:inline-block;min-width:160px;border-bottom:1px dotted #333}
  .wc h2{display:flex;align-items:baseline;gap:8px;font-size:18.5px;color:#0f5e5a;margin:16px 0 4px;border-bottom:2px solid #0f5e5a;padding-bottom:2px;break-after:avoid}
  .wc h2 i{font-style:normal;background:#0f5e5a;color:#fff;border-radius:50%;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;font-size:15px;flex:none}
  .wc h2 small{font-size:12px;color:#5f6f6c;font-weight:400;direction:ltr;margin-inline-start:auto}
  .wc h3{font-size:14.5px;color:#9a6300;margin:8px 0 2px;break-after:avoid}
  .wc ul{list-style:none;margin:0 0 4px;padding:0}
  .wc li{display:flex;gap:10px;align-items:flex-start;padding:5px 0;border-bottom:1px dashed #e3e9e7;break-inside:avoid}
  .wc li .bx{width:22px;height:22px;border:2px solid #1c2826;border-radius:5px;flex:none;margin-top:5px}
  .wc li .tx{flex:1}
  .wc li .tx em{display:block;font-style:normal;font-size:12.5px;color:#5f6f6c;line-height:1.6}
  .wc li.sh{color:#33403d}
  .wc li.sh .bx{border-color:#9fb3b0}
  .wc .gold{border:2px solid #c0362c;border-radius:10px;padding:8px 14px;margin-top:14px;background:#fff8f7;break-inside:avoid}
  .wc .gold b{color:#c0362c;font-size:16px}
  .wc .gold ol{margin:4px 0 0;padding-inline-start:22px;font-size:14.5px;line-height:1.85}
  .wc .help{margin-top:10px;background:#0f5e5a;color:#fff;border-radius:10px;padding:10px 14px;font-size:16px;font-weight:700;break-inside:avoid}
  .wc .sig{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px;font-size:14px;line-height:2.3;break-inside:avoid}
  .wc .sig div{border:1px solid #9fb3b0;border-radius:8px;padding:6px 12px}
  .wc .ft{margin-top:10px;font-size:10.5px;color:#5f6f6c;text-align:center;border-top:1px solid #0f5e5a;padding-top:4px}
  .wc .none{padding:20px;text-align:center;color:#5f6f6c}
  .ptools{max-width:820px;margin:0 auto 12px;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;font-size:13.5px}
  @page{size:A4;margin:10mm}
  @media print{
    header.top,footer.foot,.ptools,main.wrap>.alert{display:none !important}
    main.wrap{padding:0 !important;max-width:none !important}
    body{background:#fff !important}
    .wc{border:0;border-radius:0;padding:0;margin:0;max-width:none;font-size:15px}
    *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  }
</style>
@endpush

@section('content')
  @php
    $logo = $tenant->logoUrl();
    $ar = fn ($n) => strtr((string) $n, ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩']);
  @endphp

  <div class="ptools">
    <div><a href="{{ route('workcards.index') }}">← بطاقات العمل</a>
      · {{ count($list) }} بطاقة @if ($sop) · إجراء <span class="ltr">{{ $sop['code'] }}</span> {{ $sop['title'] }}@endif</div>
    <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
  </div>

  @forelse ($list as [$card, $steps, $user])
    @php
      $mgr = \App\Support\WorkCards::manager($card);
      $i = 0;
    @endphp
    <div class="wc">
      <div class="hd">
        <div class="tn">
          @if ($logo)<img src="{{ $logo }}" alt="">@endif
          <div><b>{{ $tenant->name_ar }}</b><small>إتقان · نظام إدارة الجودة</small></div>
        </div>
        <div class="tt"><b>بطاقة عملي</b><small>{{ $card['jd'] ?? $card['key'] }}{{ $sop ? ' · ' . $sop['code'] : '' }}</small></div>
      </div>

      <div class="me">
        <div><span>الاسم:</span> @if ($user)<b>{{ $user->displayName() }}</b>@else<span class="ln"></span>@endif</div>
        <div><span>الوظيفة:</span> <b>{{ $card['title'] }}</b></div>
        <div><span>مديري المباشر:</span> <b>{{ $mgr ?? '' }}</b>@unless ($mgr)<span class="ln"></span>@endunless</div>
        <div><span>منطقتي / مكان عملي:</span> <span class="ln"></span></div>
      </div>

      @if ($sop)
        <p style="margin:0 0 6px;font-size:14px;color:#5f6f6c">هذه البطاقة تخص إجراء <b>{{ $sop['title'] }}</b> فقط. الإجراء الكامل عند مديرك.</p>
      @endif

      @forelse ($steps as $code => $s)
        @php
          $i++;
        @endphp
        <h2><i>{{ $ar($i) }}</i>{{ $s['title'] }}<small>{{ $code }}</small></h2>
        @foreach ($s['phases'] as $phase => $rows)
          @if ($phase !== '' && count($s['phases']) > 1)<h3>{{ $phase }}</h3>@endif
          <ul>
            @foreach ($rows as $st)
              <li class="{{ $st['shared'] ? 'sh' : '' }}">
                <span class="bx"></span>
                <span class="tx">{{ $st['text'] }}
                  @if ($st['shared'] || $st['record'] || $st['with'])
                    <em>
                      @if ($st['shared'])للجميع ·@endif
                      @if (! $st['shared'] && $st['with'])مع: {{ implode('، ', $st['with']) }} ·@endif
                      @if ($st['record'])سجّل في: <span class="ltr">{{ $st['record'] }}</span>@endif
                    </em>
                  @endif
                </span>
              </li>
            @endforeach
          </ul>
        @endforeach
      @empty
        <div class="none">لا توجد خطوات مكتوبة لهذه الوظيفة {{ $sop ? 'في هذا الإجراء' : 'في الإجراءات' }}.</div>
      @endforelse

      <div class="gold">
        <b>⛔ قواعد لا تُكسر أبداً</b>
        <ol>@foreach ($golden as $g)<li>{{ $g }}</li>@endforeach</ol>
      </div>

      <div class="help">عند أي مشكلة أو شك: أوقف العمل وأبلغ {{ $mgr ?: 'مديرك المباشر' }} فوراً.</div>

      <div class="sig">
        <div>قرأت هذه البطاقة وفهمتها<br>توقيع الموظف: ……………………<br>التاريخ: ……… / ……… / ………</div>
        <div>شرحها للموظف<br>الاسم: ……………………………<br>التوقيع: ……………………</div>
      </div>

      <div class="ft">{{ $tenant->name_ar }} · بطاقة العمل المبسطة مأخوذة من الإجراءات المعتمدة · الإجراء الكامل هو المرجع · طُبعت <span class="ltr">{{ $printedAt }}</span></div>
    </div>
  @empty
    <div class="wc"><div class="none">لا توجد وظائف لها خطوات في هذا الإجراء.</div></div>
  @endforelse
@endsection