{{-- الوصف الوظيفي للطباعة: job · user · extra · inherited · training · tenant · note · docv · docSig · docWip --}}
  @php
    $esc = fn ($s) => e($s);
    $sections = $job['sections'];
  @endphp

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