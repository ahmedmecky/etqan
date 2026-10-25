@include('portal._head', ['title' => 'شكاوى الصيدليات'])
<main>
  <div class="card">
    <h1>تقديم شكوى</h1>
    <p class="sub">شكواك تصل مباشرة للمدير الصيدلي وإدارة الجودة، ويتم التواصل معك على رقم الموبايل.</p>
  </div>

  @if ($errors->any())
    <div class="errors">راجع ما يلي:<ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
  @endif

  <form method="POST" action="{{ route('portal.store', $token) }}" id="f" novalidate>
    @csrf
    <div class="hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

    <div class="card">
      <h2>بيانات الصيدلية</h2>
      <label class="f req" for="code">كود الصيدلية <span style="font-weight:400;color:var(--muted)">(مكتوب على الفاتورة)</span></label>
      <input type="text" id="code" name="code" class="ltr" value="{{ old('code') }}" autocomplete="off" inputmode="text">
      <div id="phBox" class="ph" hidden></div>

      <label class="f req" for="pharmacist">اسم الصيدلي</label>
      <input type="text" id="pharmacist" name="pharmacist" value="{{ old('pharmacist') }}" autocomplete="name">

      <label class="f req" for="mobile">رقم الموبايل</label>
      <input type="tel" id="mobile" name="mobile" class="ltr" value="{{ old('mobile') }}" placeholder="01xxxxxxxxx" inputmode="numeric" autocomplete="tel">
    </div>

    <div class="card">
      <h2>الشكوى عن</h2>
      <div class="choice">
        <label><input type="radio" name="about" value="product" {{ old('about') === 'product' ? 'checked' : '' }}> صنف</label>
        <label><input type="radio" name="about" value="service" {{ old('about') === 'service' ? 'checked' : '' }}> خدمة</label>
      </div>

      <div id="productBox" hidden>
        <label class="f req" for="invoice">رقم الفاتورة</label>
        <input type="text" id="invoice" name="invoice" class="ltr" value="{{ old('invoice') }}" inputmode="numeric">

        <label class="f req">الأصناف</label>
        <div id="items"></div>
        <button type="button" class="btn ghost" id="addItem">+ صنف آخر</button>
      </div>

      <div id="serviceBox" hidden>
        <label class="f req">القسم</label>
        <div class="chips">
          @foreach ($depts as $k => $label)
            <label><input type="radio" name="dept" value="{{ $k }}" {{ old('dept') === $k ? 'checked' : '' }}> {{ $label }}</label>
          @endforeach
        </div>
      </div>

      <div id="detailsBox" hidden>
        <label class="f" id="detailsLbl" for="details">تفاصيل الشكوى</label>
        <textarea id="details" name="details" placeholder="مثال: الطلبية وصلت متأخرة يومين">{{ old('details') }}</textarea>
      </div>
    </div>

    <button type="submit" class="btn block" id="send">إرسال الشكوى</button>
  </form>
</main>

<script>
const PRODUCTS = {!! json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!};
const ISSUES = {!! json_encode($issues, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!};
const OLD_ITEMS = {!! json_encode(array_values(old('items', [])), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!};
const LOOKUP = {!! json_encode(route('portal.pharmacy', $token), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!};
</script>
<script>
(function () {
  const $ = s => document.querySelector(s);
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const price = p => p === null || p === undefined ? '' : Number(p).toLocaleString('en-US', { maximumFractionDigits: 2 }) + ' ج.م';
  const norm = s => String(s).toLowerCase().replace(/[أإآ]/g, 'ا').replace(/ة/g, 'ه').replace(/ى/g, 'ي').replace(/\s+/g, ' ').trim();
  PRODUCTS.forEach(p => { p.k = norm(p.n); });
  const byId = Object.fromEntries(PRODUCTS.map(p => [p.i, p]));

  /* ---------- الصنف أو الخدمة ---------- */
  function about() {
    const v = (document.querySelector('input[name=about]:checked') || {}).value;
    $('#productBox').hidden = v !== 'product';
    $('#serviceBox').hidden = v !== 'service';
    // الحقول المخفية لا تُرسل مع الشكوى
    $('#productBox').querySelectorAll('input, select').forEach(el => { el.disabled = v !== 'product'; });
    $('#serviceBox').querySelectorAll('input').forEach(el => { el.disabled = v !== 'service'; });
    const dept = (document.querySelector('input[name=dept]:checked') || {}).value;
    $('#detailsBox').hidden = !(v === 'product' || (v === 'service' && dept));
    $('#detailsLbl').className = 'f' + (v === 'service' ? ' req' : '');
    $('#detailsLbl').textContent = v === 'service' ? 'تفاصيل الشكوى' : 'تفاصيل إضافية (اختياري)';
    if (v === 'product' && !$('#items').children.length) addItem();
  }
  document.querySelectorAll('input[name=about], input[name=dept]').forEach(r => r.addEventListener('change', about));

  /* ---------- الأصناف ---------- */
  let n = 0;
  function addItem(old) {
    const i = n++;
    const div = document.createElement('div');
    div.className = 'item';
    const p = old && byId[old.product];
    div.innerHTML = `
      <button type="button" class="x" title="حذف">×</button>
      <div class="pick">
        <input type="text" class="q" placeholder="ابحث باسم الصنف…" autocomplete="off" value="">
        <input type="hidden" name="items[${i}][product]" value="${p ? p.i : ''}">
        <div class="list" hidden></div>
      </div>
      <div class="chosen">${p ? esc(p.n) + ' · <span style="direction:ltr;display:inline-block">' + price(p.p) + '</span>' : ''}</div>
      <div class="row2" style="margin-top:8px">
        <select name="items[${i}][issue]"><option value="">— نوع المشكلة —</option>${ISSUES.map(x => `<option ${old && old.issue === x ? 'selected' : ''}>${esc(x)}</option>`).join('')}</select>
        <input type="number" name="items[${i}][qty]" min="1" placeholder="الكمية" value="${old && old.qty ? esc(old.qty) : ''}" class="ltr">
      </div>`;
    $('#items').appendChild(div);
    const on = (document.querySelector('input[name=about]:checked') || {}).value === 'product';
    div.querySelectorAll('input, select').forEach(el => { el.disabled = !on; });
    const q = div.querySelector('.q'), list = div.querySelector('.list'), hid = div.querySelector('input[type=hidden]');
    let cur = -1, shown = [];
    function show() {
      const t = norm(q.value);
      if (t.length < 2) { list.hidden = true; return; }
      const parts = t.split(' ');
      shown = PRODUCTS.filter(p => parts.every(w => p.k.includes(w))).slice(0, 40);
      cur = -1;
      list.innerHTML = shown.length ? shown.map((p, k) => `<div data-k="${k}"><span>${esc(p.n)}</span><span class="pr">${price(p.p)}</span></div>`).join('')
        : '<div style="color:var(--muted);cursor:default">لا يوجد صنف بهذا الاسم</div>';
      list.hidden = false;
    }
    function choose(k) {
      const p = shown[k]; if (!p) return;
      hid.value = p.i; q.value = '';
      div.querySelector('.chosen').innerHTML = esc(p.n) + ' · <span style="direction:ltr;display:inline-block">' + price(p.p) + '</span>';
      list.hidden = true;
    }
    q.addEventListener('input', show);
    q.addEventListener('keydown', e => {
      if (list.hidden) return;
      if (e.key === 'ArrowDown') { cur = Math.min(cur + 1, shown.length - 1); }
      else if (e.key === 'ArrowUp') { cur = Math.max(cur - 1, 0); }
      else if (e.key === 'Enter') { e.preventDefault(); choose(cur < 0 ? 0 : cur); return; }
      else return;
      e.preventDefault();
      list.querySelectorAll('[data-k]').forEach(d => d.classList.toggle('on', +d.dataset.k === cur));
    });
    list.addEventListener('mousedown', e => { const d = e.target.closest('[data-k]'); if (d) { e.preventDefault(); choose(+d.dataset.k); } });
    q.addEventListener('blur', () => setTimeout(() => { list.hidden = true; }, 150));
    div.querySelector('.x').addEventListener('click', () => { div.remove(); if (!$('#items').children.length) addItem(); });
  }
  $('#addItem').addEventListener('click', () => { if ($('#items').children.length < 10) addItem(); });

  /* ---------- اسم الصيدلية من الكود ---------- */
  let timer = null, lastCode = '';
  function lookup() {
    const code = $('#code').value.trim();
    const box = $('#phBox');
    if (code === lastCode) return;
    lastCode = code;
    if (!code) { box.hidden = true; return; }
    fetch(LOOKUP + '?code=' + encodeURIComponent(code), { headers: { 'Accept': 'application/json' } })
      .then(r => r.status === 429 ? { limited: true } : r.json())
      .then(j => {
        box.hidden = false;
        if (j.limited) { box.className = 'ph bad'; box.textContent = 'محاولات كثيرة، انتظر دقيقة ثم حاول مرة أخرى.'; return; }
        box.className = 'ph ' + (j.found ? 'ok' : 'bad');
        box.innerHTML = j.found ? '✓ ' + esc(j.name) + (j.address ? '<div style="font-weight:400;font-size:13px">' + esc(j.address) + '</div>' : '')
          : 'الكود غير مسجل، راجع الكود المكتوب على الفاتورة.';
      }).catch(() => { box.hidden = true; });
  }
  $('#code').addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(lookup, 600); });
  $('#code').addEventListener('blur', lookup);

  /* ---------- البدء ---------- */
  OLD_ITEMS.forEach(o => addItem(o));
  about();
  if ($('#code').value) lookup();
  $('#f').addEventListener('submit', () => { $('#send').disabled = true; $('#send').textContent = 'جارٍ الإرسال…'; });
})();
</script>
@include('portal._foot')