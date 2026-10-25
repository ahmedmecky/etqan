@include('portal._head', ['title' => 'تم استلام الشكوى'])
<main>
  <div class="card" style="text-align:center">
    <div style="font-size:46px;color:var(--ok);line-height:1">✓</div>
    <h1>تم استلام شكواك</h1>
    <p class="sub">رقم الشكوى</p>
    <div style="font-size:26px;font-weight:800;color:var(--brand);direction:ltr">{{ $number }}</div>
    <p class="sub" style="margin-top:12px">احتفظ بالرقم للمتابعة. سيتواصل معك المدير الصيدلي على رقم الموبايل المسجل.</p>
    <p class="sub">في شكاوى الأصناف: احتفظ بالعبوة أو العينة لحين التواصل معك.</p>
    <a href="{{ route('portal.complaint', $token) }}" class="btn ghost" style="margin-top:8px">شكوى أخرى</a>
  </div>
</main>
@include('portal._foot')