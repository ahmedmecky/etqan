@extends('layouts.app')

@section('title', 'جرد الأجهزة')

@push('head')
<style>
  .cnt{max-width:760px;margin:0 auto;padding-bottom:90px}
  .cnt .q{position:sticky;top:0;z-index:5;background:var(--bg,#f4f7f6);padding:8px 0}
  .cnt .q input{width:100%;padding:11px 14px;font-size:15px}
  .grp{margin-bottom:14px}
  .grp h2{display:flex;align-items:center;gap:8px;font-size:15px;margin:10px 0 8px}
  .grp h2 span{font-size:12.5px;color:var(--muted);font-weight:600}
  .grp h2 button{margin-inline-start:auto;background:none;border:1px solid var(--line);border-radius:99px;padding:4px 10px;font:inherit;font-size:12px;font-weight:700;color:var(--brand);cursor:pointer}
  .dv{background:#fff;border:1px solid var(--line);border-radius:12px;padding:10px 12px;margin-bottom:8px}
  .dv.ok{border-color:#bfe0c9;background:#f6fbf7} .dv.missing{border-color:#f0c4bf;background:#fff8f7} .dv.damaged{border-color:#f0dcae;background:#fffbf1}
  .dv .h{display:flex;gap:8px;align-items:baseline;flex-wrap:wrap}
  .dv .h b{font-size:15px}
  .dv .h small{font-size:12.5px;color:var(--muted)}
  .dv .sn{direction:ltr;font-weight:700;font-size:13.5px;color:var(--brand);margin-inline-start:auto}
  .ch{display:flex;gap:6px;margin-top:8px}
  .ch label{flex:1;text-align:center;border:1px solid var(--line);border-radius:10px;padding:10px 4px;font-size:14px;font-weight:700;cursor:pointer;background:#fff;user-select:none}
  .ch input{position:absolute;opacity:0;pointer-events:none}
  .ch input[value=ok]:checked + span{color:#fff} .ch label:has(input[value=ok]:checked){background:var(--ok);border-color:var(--ok);color:#fff}
  .ch label:has(input[value=missing]:checked){background:var(--bad);border-color:var(--bad);color:#fff}
  .ch label:has(input[value=damaged]:checked){background:#d98a00;border-color:#d98a00;color:#fff}
  .mv{margin-top:6px;font-size:12.5px}
  .mv summary{cursor:pointer;color:var(--muted);list-style:none}
  .mv summary::-webkit-details-marker{display:none}
  .mv input{margin-top:6px;width:100%}
  .bar{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid var(--line);padding:10px 16px;display:flex;gap:10px;align-items:center;z-index:20;box-shadow:0 -4px 16px rgba(0,0,0,.06)}
  .bar .n{flex:1;font-size:13.5px}
  .bar .n b{font-size:16px}
  .bar textarea{display:none}
  .empty{background:#fff;border:1px dashed var(--line);border-radius:12px;padding:22px;text-align:center;color:var(--muted)}
</style>
@endpush

@section('content')
<div class="cnt">
  <h1 class="page-title">جرد الأجهزة</h1>
  <p class="page-sub">امشِ على كل موقع وحدد لكل جهاز: موجود · غير موجود · تالف. إذا وجدته في مكان آخر اضغط «نُقل لموقع آخر».
    <a href="{{ route('maint.index') }}">رجوع للسجل</a></p>

  @if ($assets->isEmpty())
    <div class="empty">لا توجد أجهزة مسجلة.@if ($manage) <a href="{{ route('maint.index', ['tab' => 'assets']) }}">سجّل الأجهزة أولاً</a>.@endif</div>
  @else
    <form method="POST" action="{{ route('maint.count.save') }}" id="cntForm">
      @csrf
      <datalist id="locList">@foreach ($assets->keys() as $l)<option value="{{ $l }}">@endforeach</datalist>
      <div class="q"><input type="search" id="cq" placeholder="ابحث بالرقم التسلسلي أو الاسم…" autocomplete="off"></div>

      @foreach ($assets as $loc => $list)
        <div class="grp" data-grp>
          <h2>{{ $loc }} <span>{{ $list->count() }} جهاز</span><button type="button" data-all>الكل موجود</button></h2>
          @foreach ($list as $a)
            <div class="dv" data-dv data-txt="{{ $a->serial }} {{ $a->name }} {{ $a->tag_no }} {{ $a->categoryLabel() }}">
              <div class="h"><b>{{ $a->name }}</b><small>{{ $a->categoryLabel() }}@if ($a->status !== 'active') · {{ $a->statusLabel() }}@endif</small><span class="sn">{{ $a->serial }}</span></div>
              <div class="ch">
                <label><input type="radio" name="r[{{ $a->id }}]" value="ok"><span>✓ موجود</span></label>
                <label><input type="radio" name="r[{{ $a->id }}]" value="missing"><span>✗ غير موجود</span></label>
                <label><input type="radio" name="r[{{ $a->id }}]" value="damaged"><span>⚠ تالف</span></label>
              </div>
              <details class="mv"><summary>↔ نُقل لموقع آخر</summary><input type="text" name="loc[{{ $a->id }}]" list="locList" maxlength="120" placeholder="الموقع الجديد"></details>
            </div>
          @endforeach
        </div>
      @endforeach

      <label style="display:block;margin-top:6px;font-size:13px;font-weight:700;color:var(--muted)">ملاحظات الجرد
        <textarea name="notes" rows="2" maxlength="1000" style="width:100%;margin-top:4px" placeholder="أجهزة وجدتها غير مسجلة، ملاحظات عامة…"></textarea></label>

      <div class="bar">
        <span class="n"><b id="cDone">0</b> من {{ $assets->flatten()->count() }} · <span id="cMiss">0</span> غير موجود</span>
        <button type="submit" class="btn">حفظ الجرد</button>
      </div>
    </form>
  @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
  const form = document.getElementById('cntForm');
  if (!form) return;
  const norm = s => String(s || '').toLowerCase().replace(/[أإآ]/g, 'ا').replace(/ة/g, 'ه').replace(/ى/g, 'ي');
  function refresh() {
    let done = 0, miss = 0;
    form.querySelectorAll('[data-dv]').forEach(d => {
      const c = d.querySelector('input[type=radio]:checked');
      d.classList.remove('ok', 'missing', 'damaged');
      if (c) { d.classList.add(c.value); done++; if (c.value === 'missing') miss++; }
    });
    document.getElementById('cDone').textContent = done;
    document.getElementById('cMiss').textContent = miss;
  }
  form.addEventListener('change', refresh);
  form.querySelectorAll('[data-all]').forEach(b => b.addEventListener('click', () => {
    b.closest('[data-grp]').querySelectorAll('[data-dv]').forEach(d => {
      if (d.style.display === 'none' || d.querySelector('input[type=radio]:checked')) return;
      d.querySelector('input[value=ok]').checked = true;
    });
    refresh();
  }));
  document.getElementById('cq').addEventListener('input', e => {
    const t = norm(e.target.value.trim());
    form.querySelectorAll('[data-grp]').forEach(g => {
      let n = 0;
      g.querySelectorAll('[data-dv]').forEach(d => { const s = !t || norm(d.dataset.txt).includes(t); d.style.display = s ? '' : 'none'; if (s) n++; });
      g.style.display = n ? '' : 'none';
    });
  });
  form.addEventListener('submit', e => {
    const done = +document.getElementById('cDone').textContent;
    if (!done) { e.preventDefault(); alert('حدد نتيجة جهاز واحد على الأقل.'); }
  });
})();
</script>
@endpush