{{-- إضافة ملاحظة: $areaId (أو null = عامة) · $a · $G · $sops --}}
<datalist id="sopList">@foreach ($sops as $x)<option value="{{ $x }}">@endforeach</datalist>
<form method="POST" action="{{ route('audits.findings.add', $a) }}" class="box" style="border-style:dashed;border-color:#b9d8d3">
  @csrf
  <input type="hidden" name="area_id" value="{{ $areaId }}">
  <h2 style="color:var(--brand)">+ {{ $a->type === 'consult' ? 'توصية أو ملاحظة' : 'ملاحظة' }} {{ $areaId ? 'في هذه الإدارة' : 'عامة' }}</h2>
  <div class="grades" style="margin-bottom:10px">
    @foreach ($G as $k => $g)<label title="{{ $g[3] }}"><input type="radio" name="grade" value="{{ $k }}" @checked(old('grade', \App\Support\Audits::defaultGrade($a->type)) === $k)><i style="background:{{ $g[1] }}"></i>{{ $g[0] }}</label>@endforeach
  </div>
  <div class="mf">
    <label class="full">الملاحظة (ماذا وجدت؟) <textarea name="text" rows="3" maxlength="4000" required placeholder="مثال: ثلاجة ٢ بدون سجل حرارة يومي لمدة ٤ أيام في أكتوبر">{{ old('text') }}</textarea></label>
    <label class="two">الدليل الموضوعي (ما رأيته بنفسك) <textarea name="evidence" rows="2" maxlength="4000" placeholder="رقم السجل، التاريخ، الصنف، المكان، صورة…">{{ old('evidence') }}</textarea></label>
    <label>المرجع (المتطلب) <input type="text" name="ref" maxlength="160" list="sopList" value="{{ old('ref') }}" placeholder="SH-030 · بند GDP"></label>
  </div>
  <div class="actions" style="margin-top:10px"><button type="submit" class="btn small">إضافة الملاحظة</button></div>
</form>