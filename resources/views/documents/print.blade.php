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

  <div class="pd">
    @if ($draft)<div class="wm"><span>مسودة · غير معتمدة</span></div>@endif

    @unless ($only)
      <div class="hd">
        <div class="tn">
          @if ($logo)<img src="{{ $logo }}" alt="">@endif
          <div><b>{{ $tenant->name_ar }}</b><small>{{ $tenant->name_en }}</small><small>نظام إدارة الجودة · إتقان</small></div>
        </div>
        <div class="tt"><span>إجراء تشغيل قياسي</span><b>{{ $sop['title'] }}</b><small>{{ $sop['titleEn'] }}</small></div>
        <div class="mt">
          رقم الإجراء: <b>{{ $doc->code }}</b><br>
          الإصدار: <b>{{ $verNo }}</b><br>
          تاريخ السريان: <b>{{ $eff ?? '……………' }}</b>
        </div>
      </div>

      <table class="dates">
        <tr><td class="k">تاريخ الإصدار</td><td class="ltr" style="text-align:right">{{ $issued ?? '……………' }}</td><td class="k">تاريخ السريان</td><td class="ltr" style="text-align:right">{{ $eff ?? '……………' }}</td></tr>
        <tr><td class="k">المراجعة القادمة</td><td class="ltr" style="text-align:right">{{ $review ?? '……………' }}</td><td class="k">يحل محل</td><td>{{ $prev ? $doc->code . ' الإصدار ' . $prev : '—' }}</td></tr>
      </table>

      @foreach ($sop['sections'] as $s)
        <h3><i>{{ $s['no'] }}.</i>{{ $s['title'] }}</h3>
        @if (str_starts_with($s['title'], 'سجل التعديلات'))
          <table class="t2">
            <tr><th class="c" style="width:12%">الإصدار</th><th style="width:16%">التاريخ</th><th>وصف التعديل</th></tr>
            @forelse ($history as $h)
              <tr><td class="c ltr">{{ $h->version }}</td><td class="ltr">{{ $h->effective_date?->format('Y-m-d') ?? '……………' }}</td><td>{{ $h->change_summary }}</td></tr>
            @empty
              <tr><td class="c ltr">{{ $sop['version'] }}</td><td>……………</td><td>إصدار أول في المخزن من نص مكتبة إتقان.</td></tr>
            @endforelse
          </table>
          @continue
        @endif
        @foreach ($s['blocks'] as $b)
          @if ($b['type'] === 'p')
            <p class="{{ ! empty($b['muted']) ? 'mut' : '' }}">{{ $b['text'] }}</p>
          @elseif ($b['type'] === 'list')
            <ul>@foreach ($b['items'] as $it)<li>{{ $it }}</li>@endforeach</ul>
          @elseif ($b['type'] === 'kv')
            <table class="t2">@foreach ($b['rows'] as $r)<tr><td class="k">{{ $r[0] }}</td><td>{{ $r[1] ?? '' }}</td></tr>@endforeach</table>
          @elseif ($b['type'] === 'table')
            <table class="t2">
              <tr>@foreach ($b['head'] as $h)<th>{{ $h }}</th>@endforeach</tr>
              @foreach ($b['rows'] as $r)<tr>@foreach ($r as $c)<td>{{ $c }}</td>@endforeach</tr>@endforeach
            </table>
          @elseif ($b['type'] === 'steps')
            <table class="t2">
              <tr><th class="c" style="width:5%">{{ $b['head'][0] }}</th><th>{{ $b['head'][1] }}</th><th style="width:19%">{{ $b['head'][2] }}</th><th style="width:16%">{{ $b['head'][3] }}</th></tr>
              @foreach ($b['groups'] as $g)
                <tr class="g"><td colspan="4">{{ $g['title'] }}</td></tr>
                @foreach ($g['rows'] as $r)
                  @php
                    $n++;
                  @endphp
                  <tr><td class="c">{{ $n }}</td><td>{{ $r[0] }}</td><td>{{ $r[1] }}</td><td class="ltr" style="text-align:right">{{ $r[2] }}</td></tr>
                @endforeach
              @endforeach
            </table>
          @endif
        @endforeach
      @endforeach

      <h3><i>{{ $sop['approvalNo'] }}.</i>الاعتماد</h3>
      <table class="t2 sg">
        <tr><th style="width:16%"></th><th>إعداد</th><th>مراجعة</th><th>اعتماد</th></tr>
        <tr><td class="k">الوظيفة</td>@foreach ($sop['approvers'] as $a)<td>{{ $a }}</td>@endforeach</tr>
        <tr><td class="k">الاسم</td>@foreach (['prep', 'review', 'approve'] as $k)<td>{{ $sig[$k][0] ?? '' }}</td>@endforeach</tr>
        <tr><td class="k">التوقيع</td>@foreach (['prep', 'review', 'approve'] as $k)<td>@if (! empty($sig[$k][2]))<span class="ok">✓ معتمد إلكترونياً في إتقان</span>@endif</td>@endforeach</tr>
        <tr><td class="k">التاريخ</td>@foreach (['prep', 'review', 'approve'] as $k)<td class="ltr" style="text-align:right">{{ $sig[$k][2] ?? '' }}</td>@endforeach</tr>
      </table>
    @endunless

    @foreach ($forms as $fi => $f)
      <div class="fm {{ $only && $fi === 0 ? 'first-only' : '' }}">
        <div class="fh">
          <b><span>{{ $f['code'] }}</span>{{ $f['title'] }}</b>
          <small>{{ $tenant->name_ar }}<br>تابع للإجراء {{ $doc->code }} · الإصدار {{ $verNo }}</small>
          @if ($logo)<img src="{{ $logo }}" alt="">@endif
        </div>
        @if (! empty($f['note']))<p class="mut">{{ $f['note'] }}</p>@endif
        @if (! empty($f['fields']))
          <table class="t2">
            @foreach ($f['fields'] as $r)
              <tr>@foreach ($r as $ci => $c)<td class="{{ $ci % 2 === 0 ? 'k' : '' }}" style="{{ $ci % 2 === 0 ? 'width:18%' : 'height:28px' }}">{{ $c }}</td>@endforeach</tr>
            @endforeach
          </table>
        @endif
        @foreach ($f['parts'] ?? [] as $p)
          <h4>{{ $p['title'] }}</h4>
          @if (! empty($p['checklist']))
            <table class="t2">
              <tr><th class="c" style="width:6%">م</th><th>بند الفحص</th><th class="c" style="width:10%">مطابق</th><th class="c" style="width:10%">غير مطابق</th><th style="width:20%">ملاحظات</th></tr>
              @foreach ($p['checklist'] as $ii => $it)<tr><td class="c">{{ $ii + 1 }}</td><td>{{ $it }}</td><td class="c">☐</td><td class="c">☐</td><td></td></tr>@endforeach
            </table>
          @elseif (! empty($p['grid']))
            @php
              $gd = $p['grid'];
              $tot = array_sum($gd['widths'] ?? []) ?: 1;
              $data = $gd['data'] ?? [];
              $count = $data ? count($data) + (int) ($gd['extra'] ?? 0) : (int) ($gd['rows'] ?: 8);
            @endphp
            <table class="t2 grid">
              <tr>@foreach ($gd['cols'] as $ci => $c)<th class="{{ $ci === 0 ? 'c' : '' }}" style="width:{{ round(($gd['widths'][$ci] ?? 0) / $tot * 100, 1) }}%">{{ $c }}</th>@endforeach</tr>
              @for ($ri = 0; $ri < $count; $ri++)
                <tr>@foreach ($gd['cols'] as $ci => $c)<td class="{{ $ci === 0 ? 'c' : '' }}">{{ $ci === 0 ? $ri + 1 : ($data[$ri][$ci - 1] ?? '') }}</td>@endforeach</tr>
              @endfor
            </table>
          @elseif (! empty($p['lines']))
            @foreach ($p['lines'] as $l)<p>{{ $l }}</p>@endforeach
          @endif
        @endforeach
        @if (! empty($f['signs']))
          <table class="t2 sg" style="margin-top:10px">
            <tr>@foreach ($f['signs'] as $sg)<th>{{ $sg }}</th>@endforeach</tr>
            @foreach (['الاسم:', 'التوقيع:', 'التاريخ والوقت:'] as $lb)<tr>@foreach ($f['signs'] as $sg)<td>{{ $lb }}</td>@endforeach</tr>@endforeach
          </table>
        @endif
      </div>
    @endforeach

    <div class="foot">
      {{ $tenant->name_ar }} · وثيقة خاضعة للتحكم ({{ $doc->code }} · الإصدار {{ $verNo }}). النسخة المطبوعة غير خاضعة للتحكم ما لم تحمل ختم إدارة الجودة.
      <br>طُبعت من نظام إتقان بتاريخ <span class="ltr">{{ $printedAt }}</span> بواسطة {{ $printedBy }}.
    </div>
  </div>
@endsection