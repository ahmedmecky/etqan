@extends('layouts.app')

@section('title', 'الوصف الوظيفي · ' . ($user ? $user->displayName() : $job['title']))

@push('head')
<style>
  .jd{max-width:900px;margin:0 auto;background:#fff;border:1px solid var(--line);border-radius:12px;padding:26px 30px;font-size:14px;line-height:1.85}
  .jd .hd{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;border-bottom:3px solid #c08a1e;padding-bottom:8px;margin-bottom:12px;gap:10px}
  .jd .hd .tn{font-weight:700;color:#0f5e5a;font-size:15px}
  .jd .hd .tt{text-align:center}
  .jd .hd .tt b{display:block;font-size:20px;color:#0f5e5a}
  .jd .hd .tt span{font-size:12px;color:#5f6f6c;direction:ltr;display:inline-block}
  .jd .hd img{max-height:46px;justify-self:end}
  .jd h3{font-size:15px;color:#0f5e5a;border-bottom:1.5px solid #0f5e5a;padding-bottom:2px;margin:18px 0 8px}
  .jd h4{font-size:14px;margin:10px 0 4px;color:#24514d}
  .jd ul{margin:4px 0 6px;padding-inline-start:22px}
  .jd li{margin-bottom:2px}
  .jd table{width:100%;border-collapse:collapse;font-size:13px;margin:4px 0 8px}
  .jd th,.jd td{border:1px solid #9fb3b0;padding:5px 8px;text-align:start;vertical-align:top}
  .jd th{background:#e3f0ee;color:#0f5e5a}
  .jd td.k{background:#f2f7f6;font-weight:700;color:#24514d;width:26%}
  .jd .emp{background:#f6faf9;border:1px solid #d3e8e3;border-radius:10px;padding:10px 14px;margin-bottom:6px;display:grid;grid-template-columns:repeat(2,1fr);gap:4px 18px;font-size:13.5px}
  .jd .emp b{color:#0f5e5a}
  .jd .xtra{border:1.5px dashed #c08a1e;border-radius:10px;padding:8px 14px;margin-top:10px;background:#fffdf7}
  .jd .xtra h4{color:#9a6300}
  .jd .src{font-size:11.5px;color:#9a6300;font-weight:700}
  .jd .note{background:#f6f8f7;border-radius:8px;padding:6px 10px;font-size:13px}
  .tools{max-width:900px;margin:0 auto 12px;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center}
  .tools form{display:flex;gap:6px;align-items:center;margin:0}
  .tools input[type=date]{width:auto;direction:ltr}
  .ack{color:var(--ok);font-weight:700}
  @page{size:A4;margin:12mm}
  @media print{
    header.top,footer.foot,.tools,main.wrap>.alert{display:none !important}
    main.wrap{padding:0 !important;max-width:none !important}
    body{background:#fff !important}
    .jd{border:0;padding:0;max-width:none;font-size:12px;line-height:1.7}
    .jd h3{break-after:avoid}
    .jd table{break-inside:avoid}
    .jd .xtra h4{break-after:avoid}
    *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  }
</style>
@endpush

@section('content')
  @php
    $esc = fn ($s) => e($s);
    $sections = $job['sections'];
  @endphp

  <div class="tools">
    <div>
      @if ($user)
        <b>{{ $user->displayName() }}</b> ·
        @if ($user->jd_ack_at)<span class="ack">✓ وقّع بالاستلام {{ $user->jd_ack_at->format('Y-m-d') }}</span>@else<span style="color:var(--bad)">لم يُسجل إقرار الاستلام</span>@endif
      @else
        <b>نسخة فارغة للتعيين</b>
      @endif
    </div>
    <div class="actions" style="gap:6px">
      @if ($user && $canManage && ! $user->jd_ack_at)
        <form method="POST" action="{{ route('jobs.ack', $user) }}">
          @csrf
          <input type="date" name="date" value="{{ $today }}" max="{{ $today }}">
          <button type="submit" class="btn ghost small">تسجيل توقيع الموظف</button>
        </form>
      @endif
      <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
    </div>
  </div>

  <div class="jd">
    <div class="hd">
      <div class="tn">{{ $tenant->name_ar }}<div style="font-size:11px;color:#5f6f6c;font-weight:400">إتقان · نظام إدارة الجودة</div></div>
      <div class="tt"><b>الوصف الوظيفي</b><span>{{ $job['code'] }} · {{ $job['title'] }}</span>
        <span style="display:block;font-size:11.5px">@if ($docv)الإصدار {{ $docv->version }} · ساري من {{ $docv->effective_date?->format('Y-m-d') }}@else<b style="display:inline;font-size:11.5px;color:#b3261e">غير معتمد بعد في التحكم في الوثائق{{ $docWip ? ' (' . $docWip . ')' : '' }}</b>@endif</span></div>
      @if ($tenant->logoUrl())<img src="{{ $tenant->logoUrl() }}" alt="">@else<span></span>@endif
    </div>

    @if ($user)
      <div class="emp">
        <div>الموظف: <b>{{ $user->displayName() }}</b></div>
        <div>تاريخ التعيين: <b class="ltr">{{ $user->hire_date?->format('Y-m-d') ?? '……………' }}</b></div>
        <div>الوظيفة: <b>{{ $job['title'] }}</b></div>
        <div>وظائف إضافية: <b>{{ collect($extra)->pluck('title')->implode('، ') ?: '—' }}</b></div>
      </div>
    @endif

    @foreach ($sections as $s)
      @php
        $isDuties = str_starts_with($s['title'], 'الواجبات');
        $isTraining = str_starts_with($s['title'], 'التدريب');
        $isAck = str_starts_with($s['title'], 'إقرار');
      @endphp
      <h3>{{ $s['no'] }}. {{ $s['title'] }}</h3>

      @foreach ($s['blocks'] as $b)
        @if ($b['type'] === 'p')
          <p style="margin:4px 0">{{ $b['text'] }}</p>
        @elseif ($b['type'] === 'list')
          <ul>@foreach ($b['items'] as $it)<li>{{ $it }}</li>@endforeach</ul>
        @elseif ($b['type'] === 'group')
          <h4>{{ $b['key'] }}. {{ $b['title'] }}</h4>
          <ul>@foreach ($b['items'] as $it)<li>{{ $it }}</li>@endforeach</ul>
        @elseif ($b['type'] === 'kv')
          <table>@foreach ($b['rows'] as $r)<tr><td class="k">{{ $r[0] }}</td><td>{{ $r[1] ?? '' }}</td></tr>@endforeach</table>
        @elseif ($b['type'] === 'table')
          @if (str_starts_with($s['title'], 'الاعتماد') && $docv)
            <table>
              <tr>@foreach ($b['head'] as $h)<th>{{ $h }}</th>@endforeach</tr>
              <tr>@foreach ($b['rows'][0] ?? [] as $c)<td>{{ $c }}</td>@endforeach</tr>
              <tr><td class="k">الاسم</td>@foreach (['prep', 'review', 'approve'] as $k)<td>{{ $docSig[$k][0] ?? '' }}</td>@endforeach</tr>
              <tr><td class="k">التوقيع</td>@foreach (['prep', 'review', 'approve'] as $k)<td>@if (! empty($docSig[$k][2]))<span style="color:#1e7a46;font-weight:700">✓ معتمد إلكترونياً في إتقان</span>@endif</td>@endforeach</tr>
              <tr><td class="k">التاريخ</td>@foreach (['prep', 'review', 'approve'] as $k)<td class="ltr">{{ $docSig[$k][2] ?? '' }}</td>@endforeach</tr>
            </table>
          @elseif ($isAck)
            <table><tr>@foreach ($b['head'] as $h)<th>{{ $h }}</th>@endforeach</tr>
              <tr><td style="height:34px">{{ $user?->displayName() }}</td><td></td><td class="ltr">{{ $user?->jd_ack_at?->format('Y-m-d') }}</td></tr></table>
          @else
            <table>
              <tr>@foreach ($b['head'] as $h)<th>{{ $h }}</th>@endforeach</tr>
              @foreach ($isTraining && $user && count($extra) ? $training : $b['rows'] as $r)
                <tr>@foreach ($r as $c)<td>{{ $c }}</td>@endforeach</tr>
              @endforeach
            </table>
          @endif
        @endif
      @endforeach

      @if ($isDuties && $user && (count($extra) || count($inherited)))
        <div class="xtra">
          <h4>مهام إضافية مكلف بها</h4>
          @foreach ($extra as $x)
            <div class="src">من الوظيفة الإضافية: {{ $x['code'] }} · {{ $x['title'] }}</div>
            @foreach (\App\Support\JobLibrary::groups($x) as $g)
              <h4 style="margin-top:6px">{{ $g['key'] }}. {{ $g['title'] }}</h4>
              <ul>@foreach ($g['items'] as $it)<li>{{ $it }}</li>@endforeach</ul>
            @endforeach
          @endforeach
          @foreach ($inherited as $h)
            <div class="src">لشغور وظيفة: {{ $h['from'] }} · {{ $h['fromTitle'] }}</div>
            <h4 style="margin-top:4px">{{ $h['group']['key'] }}. {{ $h['group']['title'] }}</h4>
            <ul>@foreach ($h['group']['items'] as $it)<li>{{ $it }}</li>@endforeach</ul>
          @endforeach
        </div>
      @endif
      @if ($isDuties && $note)
        <div class="note"><b>ملاحظة المخزن:</b> {{ $note }}</div>
      @endif
    @endforeach
  </div>
@endsection