@include('portal._head', ['title' => 'تفتيش ميداني · ' . $d->name])
<style>
  .who{display:flex;justify-content:space-between;align-items:center;gap:8px;background:var(--brand-soft);border-radius:12px;padding:10px 14px;margin-bottom:14px;font-size:14px}
  .who b{color:var(--brand)}
  .done{background:var(--ok-soft);color:var(--ok);border-radius:12px;padding:14px;margin-bottom:14px;font-weight:700}
  .done.warn{background:#fff6e8;color:#9a6300}
  .item h3{margin:0 0 6px;font-size:14px;color:var(--brand)}
  .pair{display:grid;grid-template-columns:1fr 1fr;gap:8px}
  .pair label.f{margin-top:8px}
  .pair .h{font-size:12px;color:var(--muted);font-weight:700;margin-top:10px;text-align:center;border-bottom:1px dashed var(--line);padding-bottom:3px}
  input[type=month]{width:100%;padding:10px 10px;border:1px solid var(--line);border-radius:10px;font:inherit;font-size:16px;background:#fff;direction:ltr}
  .flags{margin-top:8px;display:flex;flex-wrap:wrap;gap:6px}
  .flags span{background:var(--bad-soft);color:var(--bad);border-radius:99px;padding:2px 10px;font-size:12.5px;font-weight:700}
  .flags span.ok{background:var(--ok-soft);color:var(--ok)}
  .item.bad{border-color:#efb7b2;background:#fff8f7}
  .recent div{display:flex;justify-content:space-between;gap:8px;padding:7px 0;border-bottom:1px solid #eef1f0;font-size:13px}
  .recent div:last-child{border:0}
  .recent b{direction:ltr}
  .sticky{position:sticky;bottom:0;background:var(--bg);padding:10px 0}
</style>
<main>
  <div class="who"><span>المفتش: <b>{{ $d->name }}</b>{{ $d->job ? ' · ' . $d->job : '' }}</span><span class="ltr" style="color:var(--muted)">SH-012</span></div>

  @if (session('saved'))
    @php
      $s = session('saved');
    @endphp
    <div class="done {{ $s['bad'] ? 'warn' : '' }}">✓ تم الحفظ برقم <span class="ltr">{{ $s['no'] }}</span> · {{ $s['n'] }} صنف
      @if ($s['bad']) · {{ $s['bad'] }} عدم مطابقة (يُبلغ المدير الصيدلي) @else · كل الأصناف مطابقة @endif</div>
  @endif

  @if ($errors->any())
    <div class="errors">لم يتم الحفظ:<ul>@foreach ($errors->unique() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <form method="POST" action="{{ route('field.store', $token) }}" id="f" autocomplete="off">
    @csrf
    <input type="text" name="website" class="hp" tabindex="-1" aria-hidden="true">

    <div class="card">
      <h2>الفاتورة</h2>
      <label class="f req" for="invoice">رقم الفاتورة</label>
      <input type="text" id="invoice" name="invoice" value="{{ old('invoice') }}" class="ltr" inputmode="numeric" required>

      <label class="f req" for="phq">الصيدلية (الكود أو الاسم)</label>
      <div class="pick">
        <input type="text" id="phq" name="pharmacy" value="{{ old('pharmacy') }}" placeholder="اكتب الكود أو جزءاً من الاسم" required>
        <div class="list" id="phList" hidden></div>
      </div>
      <input type="hidden" name="pharmacy_code" id="phCode" value="{{ old('pharmacy_code') }}">
      <div class="chosen" id="phChosen"></div>
    </div>

    <div class="card">
      <h2>الأصناف</h2>
      <p class="sub">لكل صنف: التشغيلة والصلاحية المكتوبة على الفاتورة، ثم الموجودة فعلاً على العلبة.</p>
      <div id="items"></div>
      <button type="button" class="btn ghost" id="addItem">+ إضافة صنف</button>
    </div>

    <div class="card">
      <label class="f" for="notes" style="margin-top:0">ملاحظات عامة</label>
      <textarea name="notes" id="notes" style="min-height:70px">{{ old('notes') }}</textarea>
    </div>

    <div class="sticky"><button type="submit" class="btn block" id="save">حفظ التفتيش</button></div>
  </form>

  @if ($recent->count())
    <div class="card recent">
      <h2>آخر ما سجلته</h2>
      @foreach ($recent as $r)
        <div><span>{{ $r->title }}</span><b>{{ $r->number }}</b></div>
      @endforeach
    </div>
  @endif
</main>

<template id="tpl">
  <div class="item">
    <button type="button" class="x" title="حذف">×</button>
    <h3>صنف <span class="no"></span></h3>
    <label class="f req">الصنف</label>
    <div class="pick">
      <input type="text" data-k="name" placeholder="اكتب حرفين من اسم الصنف">
      <div class="list" hidden></div>
    </div>
    <input type="hidden" data-k="product">
    <div class="pair">
      <div class="h">على الفاتورة</div><div class="h">الفعلي (على العلبة)</div>
      <div><label class="f req">التشغيلة</label><input type="text" data-k="batchInv" class="ltr"></div>
      <div><label class="f req">التشغيلة</label><input type="text" data-k="batchReal" class="ltr"></div>
      <div><label class="f req">الصلاحية</label><input type="month" data-k="expInv"></div>
      <div><label class="f req">الصلاحية</label><input type="month" data-k="expReal"></div>
    </div>
    <label class="f">الزون / الموقع</label>
    <input type="text" data-k="zone" class="ltr">
    <label class="f">ملاحظات</label>
    <input type="text" data-k="note">
    <div class="flags"></div>
  </div>
</template>

<script>
(function () {
  const BASE = @json(url('/i/' . $token));
  const OLD = @json(array_values(old('items', [])));
  const box = document.getElementById('items');
  const tpl = document.getElementById('tpl');
  const today = new Date(); const ym = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0');
  const norm = v => (v || '').replace(/\s+/g, '').toUpperCase();
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  let tmr;

  function renumber() {
    box.querySelectorAll('.item').forEach((el, i) => {
      el.querySelector('.no').textContent = i + 1;
      el.querySelectorAll('[data-k]').forEach(inp => { inp.name = `items[${i}][${inp.dataset.k}]`; });
      el.querySelector('.x').hidden = box.children.length === 1;
    });
  }
  function check(el) {
    const v = k => el.querySelector(`[data-k=${k}]`).value.trim();
    const out = [];
    if (v('batchInv') && v('batchReal') && norm(v('batchInv')) !== norm(v('batchReal'))) out.push('التشغيلة مختلفة');
    if (v('expInv') && v('expReal') && v('expInv') !== v('expReal')) out.push('الصلاحية مختلفة');
    if (v('expReal') && v('expReal') < ym) out.push('منتهي الصلاحية');
    const full = v('batchInv') && v('batchReal') && v('expInv') && v('expReal');
    el.classList.toggle('bad', out.length > 0);
    el.querySelector('.flags').innerHTML = out.length ? out.map(t => `<span>${t}</span>`).join('') : (full ? '<span class="ok">مطابق ✓</span>' : '');
  }
  function lookup(input, list, url, render, pick) {
    input.addEventListener('input', () => {
      clearTimeout(tmr);
      const q = input.value.trim();
      if (q.length < (url.includes('products') ? 2 : 1)) { list.hidden = true; return; }
      tmr = setTimeout(async () => {
        try {
          const r = await fetch(url + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
          const j = await r.json();
          list.innerHTML = (j.list || []).map((x, i) => `<div data-i="${i}">${render(x)}</div>`).join('');
          list.hidden = !(j.list || []).length;
          list.onclick = e => { const d = e.target.closest('[data-i]'); if (!d) return; pick(j.list[d.dataset.i]); list.hidden = true; };
        } catch (e) { list.hidden = true; }
      }, 250);
    });
    input.addEventListener('blur', () => setTimeout(() => { list.hidden = true; }, 200));
  }
  function add(data) {
    const el = tpl.content.firstElementChild.cloneNode(true);
    box.appendChild(el);
    if (data) Object.keys(data).forEach(k => { const i = el.querySelector(`[data-k=${k}]`); if (i) i.value = data[k] ?? ''; });
    const name = el.querySelector('[data-k=name]');
    lookup(name, el.querySelector('.list'), BASE + '/products', x => `<span>${esc(x.n)}</span><span class="pr">${esc(x.l || '')}</span>`, x => {
      name.value = x.n; el.querySelector('[data-k=product]').value = x.i;
      const z = el.querySelector('[data-k=zone]'); if (!z.value && x.l) z.value = x.l;
      el.querySelector('[data-k=batchInv]').focus();
    });
    name.addEventListener('input', () => { el.querySelector('[data-k=product]').value = ''; });
    el.addEventListener('input', () => check(el));
    el.querySelector('.x').addEventListener('click', () => { el.remove(); renumber(); });
    renumber(); check(el);
    return el;
  }

  // الصيدلية
  const phq = document.getElementById('phq'), phCode = document.getElementById('phCode'), phChosen = document.getElementById('phChosen');
  lookup(phq, document.getElementById('phList'), BASE + '/pharmacies', x => `<span>${esc(x.n)}</span><span class="pr">${esc(x.c)}</span>`, x => {
    phq.value = x.n; phCode.value = x.c; phChosen.textContent = `✓ ${x.n} · كود ${x.c}${x.a ? ' · ' + x.a : ''}`;
  });
  phq.addEventListener('input', () => { phCode.value = ''; phChosen.textContent = ''; });
  if (phCode.value) phChosen.textContent = `✓ ${phq.value} · كود ${phCode.value}`;

  document.getElementById('addItem').addEventListener('click', () => add().querySelector('[data-k=name]').focus());
  (OLD.length ? OLD : [null]).forEach(add);
  document.getElementById('f').addEventListener('submit', () => { const b = document.getElementById('save'); b.disabled = true; b.textContent = 'جارٍ الحفظ…'; });
})();
</script>
@include('portal._foot')