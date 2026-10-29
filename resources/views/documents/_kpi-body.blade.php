{{-- مجموعة مؤشرات الأداء للطباعة: doc · v · tenant · prev · sig · draft · set · approvers · bands · gateCap · printedAt · printedBy --}}
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

  <div class="pd">
    @if ($draft)<div class="wm"><span>مسودة · غير معتمدة</span></div>@endif

    <div class="hd">
      <div class="tn">
        @if ($logo)<img src="{{ $logo }}" alt="">@endif
        <div><b>{{ $tenant->name_ar }}</b><small>{{ $tenant->name_en }}</small><small>نظام إدارة الجودة · إتقان</small></div>
      </div>
      <div class="tt"><span>مؤشرات قياس الأداء الشهري</span><b>{{ $set['title'] }}</b><small>{{ $doc->code }}</small></div>
      <div class="mt">
        رقم الوثيقة: <b>{{ $doc->code }}</b><br>
        الإصدار: <b>{{ $verNo }}</b><br>
        تاريخ السريان: <b>{{ $eff ?? '……………' }}</b>
      </div>
    </div>

    <table class="dates">
      <tr><td class="k">تاريخ الإصدار</td><td class="ltr" style="text-align:right">{{ $issued ?? '……………' }}</td><td class="k">تاريخ السريان</td><td class="ltr" style="text-align:right">{{ $eff ?? '……………' }}</td></tr>
      <tr><td class="k">المراجعة القادمة</td><td class="ltr" style="text-align:right">{{ $review ?? '……………' }}</td><td class="k">يحل محل</td><td>{{ $prev ? $doc->code . ' الإصدار ' . $prev : '—' }}</td></tr>
    </table>

    <h3><i>١.</i>الوظائف المشمولة</h3>
    <table class="t2">
      <tr><th style="width:12%">الرمز</th><th>الوظيفة</th><th>الإدارة / الوصف الوظيفي</th></tr>
      @foreach ($variants as $vk => $va)<tr><td class="c">{{ $vk }}</td><td>{{ $va['name'] }}</td><td>{{ $va['dept'] }}</td></tr>@endforeach
    </table>
    @if (! empty($set['ref']))<p class="mut">المراجع: {{ $set['ref'] }}</p>@endif

    <h3><i>٢.</i>المؤشرات وأوزانها</h3>
    <table class="t2">
      <tr><th style="width:8%">الكود</th><th style="width:15%">المؤشر</th><th>التعريف وطريقة الحساب</th><th style="width:14%">المصدر · التكرار</th><th style="width:15%">الحد الأدنى والسقف</th>
        @foreach ($variants as $vk => $va)<th class="c" style="width:6%">الوزن {{ count($variants) > 1 ? $vk : '' }}</th>@endforeach</tr>
      @foreach ($set['groups'] as $gk => $gt)
        <tr class="g"><td colspan="{{ 5 + count($variants) }}">{{ $gt }}</td></tr>
        @foreach (collect($set['kpis'])->where('g', $gk) as $k)
          <tr>
            <td class="ltr" style="white-space:nowrap">{{ $k['code'] }}</td>
            <td><b>{{ $k['name'] }}</b></td>
            <td>{{ $k['def'] }}@if (! empty($k['a']) && ! empty($k['t']))<div class="mut" style="font-size:11.5px">{{ $k['a'] }} ÷ {{ $k['t'] }}</div>@endif</td>
            <td>{{ $k['src'] ?? '' }}<div class="mut" style="font-size:11.5px">{{ $k['freq'] ?? '' }}</div></td>
            <td>{{ $range($k) }}</td>
            @foreach ($variants as $vk => $va)<td class="c">{{ isset($k['w'][$vk]) ? $pct($k['w'][$vk]) : '—' }}</td>@endforeach
          </tr>
        @endforeach
      @endforeach
    </table>

    @if (! empty($set['gates']))
      <h3><i>٣.</i>المخالفات الجوهرية</h3>
      <p>عند ثبوت أي من المخالفات التالية خلال الشهر لا تتجاوز النتيجة النهائية {{ $pct($gateCap) }} مهما كانت نتائج المؤشرات:</p>
      <ul>@foreach ($set['gates'] as $g)<li>{{ $g }}</li>@endforeach</ul>
    @endif

    @if (! empty($set['rubric']))
      <h3><i>٤.</i>معيار تقييم المدير (السلوك)</h3>
      <table class="t2">
        <tr><th class="c" style="width:10%">الدرجة</th><th>الوصف</th></tr>
        @foreach ($set['rubric'] as $r)<tr><td class="c">{{ $r[0] }}</td><td>{{ $r[1] }}</td></tr>@endforeach
      </table>
    @endif

    <h3><i>٥.</i>التقدير العام</h3>
    <table class="t2">
      <tr><th>التقدير</th><th class="c" style="width:22%">النتيجة من</th></tr>
      @foreach ($bands as $bd)<tr><td>{{ $bd['name'] }}</td><td class="c">{{ $pct($bd['min']) }}</td></tr>@endforeach
    </table>

    <h3><i>٦.</i>الاعتماد</h3>
    <table class="t2 sg">
      <tr><th style="width:16%"></th><th>إعداد</th><th>مراجعة</th><th>اعتماد</th></tr>
      <tr><td class="k">الوظيفة</td>@foreach ($approvers as $a)<td>{{ $a }}</td>@endforeach</tr>
      <tr><td class="k">الاسم</td>@foreach (['prep', 'review', 'approve'] as $k)<td>{{ $sig[$k][0] ?? '' }}</td>@endforeach</tr>
      <tr><td class="k">التوقيع</td>@foreach (['prep', 'review', 'approve'] as $k)<td>@if (! empty($sig[$k][2]))<span class="ok">✓ معتمد إلكترونياً في إتقان</span>@endif</td>@endforeach</tr>
      <tr><td class="k">التاريخ</td>@foreach (['prep', 'review', 'approve'] as $k)<td class="ltr" style="text-align:right">{{ $sig[$k][2] ?? '' }}</td>@endforeach</tr>
    </table>

    <div class="foot">
      {{ $tenant->name_ar }} · وثيقة خاضعة للتحكم ({{ $doc->code }} · الإصدار {{ $verNo }}). النسخة المطبوعة غير خاضعة للتحكم ما لم تحمل ختم إدارة الجودة.
      <br>طُبعت من نظام إتقان بتاريخ <span class="ltr">{{ $printedAt }}</span> بواسطة {{ $printedBy }}.
    </div>
  </div>